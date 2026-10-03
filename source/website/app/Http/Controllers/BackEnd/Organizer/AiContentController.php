<?php

namespace App\Http\Controllers\BackEnd\Organizer;

use App\Http\Controllers\Controller;
use App\Models\OrganizerAiBalance;
use App\Services\Ai\AiContentService;
use App\Services\Ai\AiTextManager;
use App\Services\Ai\AiTokenUsageService;
use App\Services\Ai\ContentGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AiContentController extends Controller
{
    private function getCurrentBalance(int $organizerId, string $engine): ?OrganizerAiBalance
    {
        return OrganizerAiBalance::query()
            ->where('organizer_id', $organizerId)
            ->where('ai_engine', $engine)
            ->first();
    }

    private function getRemainingTokenBalance(?OrganizerAiBalance $organizerAiBalance): int
    {
        if (!$organizerAiBalance) {
            return 0;
        }

        return max(
            0,
            (int) $organizerAiBalance->ai_token_balance - (int) $organizerAiBalance->total_ai_token_used
        );
    }

    private function formatEngineLabel(string $engine): string
    {
        return match (strtolower($engine)) {
            'openai' => 'OpenAI',
            'gemini' => 'Gemini',
            'pollinations' => 'Pollinations',
            default => ucfirst($engine),
        };
    }

    private function buildTokenLimitMessage(OrganizerAiBalance $organizerAiBalance, int $estimatedTokens = 0): string
    {
        $remaining = $this->getRemainingTokenBalance($organizerAiBalance);
        $engineLabel = $this->formatEngineLabel((string) $organizerAiBalance->ai_engine);

        if ($remaining <= 0) {
            return "Your {$engineLabel} AI content token limit has been reached. Please purchase a new package to continue.";
        }

        if ($estimatedTokens > 0 && $remaining < $estimatedTokens) {
            return "You do not have enough {$engineLabel} AI content tokens remaining for this request. Please purchase a new package to continue.";
        }

        return '';
    }

    public function generateContent(
        Request $request,
        AiTextManager $aiText,
        AiContentService $contentService,
        AiTokenUsageService $tokenUsage,
        ContentGenerator $generator
    ) {
       
  
        // Basic request validation
        $validator = Validator::make($request->all(), [
            'prompt'            => 'required|string|min:2|max:2000',
            'mode'              => 'nullable|string',
            'field'             => 'nullable|string',
            'lang'              => 'nullable|string',
            'engine'            => 'required|in:pollinations,openai,gemini',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // Resolve user and preferred engine
        $organizer = Auth::guard('organizer')->user();
        $organizer_id = $organizer->id;
        $engine = strtolower(trim((string) $request->engine));
        $currentMembership = null;

        if ($engine !== 'pollinations') {
            $currentMembership = $this->getCurrentBalance($organizer_id, $engine);

            if (!$currentMembership) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No AI content token balance was found for the selected engine.',
                ], 422);
            }
        }
        

        // Resolve target languages for the user 
        $targets = $contentService->getTargets((int) $organizer->id, $request->input('lang'));

        $targetLangCodes = array_map(fn($t) => $t['code'], $targets);

        // Build prompts by mode
        $baseIdea = trim((string) $request->prompt);
        $mode = trim((string) $request->input('mode', ''));

        $homeField = '';
        if ($mode === 'home_page_text') {
            $homeField = $contentService->sanitizeHomeField((string) $request->input('field', ''));
            if ($homeField === '') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid field for home page text.',
                ], 422);
            }
        }
        $mailField = '';
        if ($mode === 'mail') {
            $mailField = $contentService->sanitizeMailField((string) $request->input('field', ''));
            if ($mailField === '') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid field for mail content.',
                ], 422);
            }
        }

        $prompts = $contentService->buildPrompts(
            mode: $mode,
            baseIdea: $baseIdea,
            request: $request,
            targets: $targets,
            targetLangCodes: $targetLangCodes,
            engine: $engine,
            generator: $generator
        );

        if ($engine !== 'pollinations' && $currentMembership) {
            $estimatedTokens = 0;
            foreach ($prompts as $prompt) {
                $estimatedTokens += $tokenUsage->estimateTokens((string) $prompt);
            }

            $limitMessage = $this->buildTokenLimitMessage($currentMembership, $estimatedTokens);
            if ($limitMessage !== '') {
                return response()->json([
                    'status'  => false,
                    'message' => $limitMessage,
                ], 422);
            }
        }

        try {
            // Run prompts and merge JSON responses.
            $json = $contentService->runPrompts(
                $prompts,
                $aiText,
                $engine,
                $generator,
                $currentMembership,
                $tokenUsage
            );

            if (!is_array($json) || $json === []) {
                return response()->json([
                    'status'  => false,
                    'message' => 'AI did not return valid JSON.',
                ], 422);
            }

            if ($mode === 'home_page_text') {
                $value = isset($json[$homeField]) ? trim((string) $json[$homeField]) : '';
                if ($value === '') {
                    return response()->json([
                        'status'  => false,
                        'message' => 'AI did not return valid JSON for the requested field.',
                    ], 422);
                }

                return response()->json([
                    'status' => true,
                    'names' => [$homeField => $value],
                ]);
            }

            if ($mode === 'mail') {
                $value = isset($json[$mailField]) ? trim((string) $json[$mailField]) : '';
                if ($value === '') {
                    return response()->json([
                        'status'  => false,
                        'message' => 'AI did not return valid JSON for the requested field.',
                    ], 422);
                }

                return response()->json([
                    'status' => true,
                    'names' => [$mailField => $value],
                ]);
            }

            if ($mode === 'additional_section') {
                if (!is_array($json) || $json === []) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'AI did not return valid JSON for the requested field(s).',
                    ], 422);
                }

                $requestedField = trim((string) $request->input('field', ''));
                if ($requestedField !== '' && empty($json[$requestedField])) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'AI did not return valid JSON for the requested field.',
                    ], 422);
                }

                $hasAny = false;
                foreach ($json as $value) {
                    if (trim((string) $value) !== '') {
                        $hasAny = true;
                        break;
                    }
                }

                if (!$hasAny) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'AI did not return valid JSON for the requested field(s).',
                    ], 422);
                }

                return response()->json([
                    'status' => true,
                    'names' => $json,
                ]);
            }

            if ($mode === 'faq') {
                $question = isset($json['question']) ? trim((string) $json['question']) : '';
                $answer = isset($json['answer']) ? trim((string) $json['answer']) : '';

                if ($question === '' || $answer === '') {
                    return response()->json([
                        'status'  => false,
                        'message' => 'AI did not return valid FAQ JSON.',
                    ], 422);
                }

                return response()->json([
                    'status' => true,
                    'names' => [
                        'question' => $question,
                        'answer' => $answer,
                    ],
                ]);
            }

            if ($mode === 'page') {
                $requestedField = trim((string) $request->input('field', ''));
                if ($requestedField !== '') {
                    $value = isset($json[$requestedField]) ? trim((string) $json[$requestedField]) : '';

                    if ($value === '') {
                        if (str_ends_with($requestedField, '_title') && !empty($json['title'])) {
                            $value = trim((string) $json['title']);
                        } elseif (str_ends_with($requestedField, '_body') && !empty($json['body'])) {
                            $value = trim((string) $json['body']);
                        }
                    }

                    if ($value === '') {
                        return response()->json([
                            'status'  => false,
                            'message' => 'AI did not return valid Page JSON.',
                        ], 422);
                    }

                    return response()->json([
                        'status' => true,
                        'names' => [
                            $requestedField => $value,
                        ],
                    ]);
                }
                

                $out = [];
                foreach ($targetLangCodes as $code) {
                    $tKey = "{$code}_title";
                    $bKey = "{$code}_body";
                    $out[$tKey] = isset($json[$tKey]) ? trim((string) $json[$tKey]) : '';
                    $out[$bKey] = isset($json[$bKey]) ? trim((string) $json[$bKey]) : '';
                }

                if (!array_filter($out)) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'AI did not return valid Page JSON.',
                    ], 422);
                }

                return response()->json([
                    'status' => true,
                    'names' => $out,
                ]);
            }

            if ($mode === 'item_seo') {
                // Return a flat {lang_field => value} map for item SEO mode.
                $requestedField = (string) $request->input('field', '');
                $requestedLang  = (string) $request->input('lang', '');

                $fields = in_array($requestedField, AiContentService::ALLOWED_FIELDS, true)
                    ? [$requestedField]
                    : AiContentService::ALLOWED_FIELDS;

                $langCodes = (is_string($requestedLang) && in_array($requestedLang, $targetLangCodes, true))
                    ? [$requestedLang]
                    : $targetLangCodes;

                $out = [];
                foreach ($langCodes as $code) {
                    foreach ($fields as $f) {
                        $key = "{$code}_{$f}";
                        $out[$key] = isset($json[$key]) ? trim((string) $json[$key]) : '';
                    }
                }

                $hasAny = false;
                foreach ($out as $value) {
                    if ($value !== '') {
                        $hasAny = true;
                        break;
                    }
                }

                if (!$hasAny) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'AI did not return valid SEO JSON for the requested field(s)/language(s).',
                    ], 422);
                }

                return response()->json(['status' => true, 'names' => $out]);
            }

            // Default modes: map each language code to its generated text
            $out = [];
            foreach ($targets as $t) {
                $out[$t['code']] = isset($json[$t['code']]) ? trim((string) $json[$t['code']]) : '';
            }

            if (!array_filter($out)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'AI did not return valid JSON for the required languages.',
                ], 422);
            }

            return response()->json(['status' => true, 'names' => $out]);
        } catch (\Throwable $e) {
            // Log and return a generic error 
            Log::error('AI failed', [
                'engine' => $engine,
                'mode' => $mode,
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'status'  => false,
                'message' => 'AI failed',
            ], 500);
        }
    }
}
