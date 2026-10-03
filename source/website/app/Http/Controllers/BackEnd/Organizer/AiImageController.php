<?php

namespace App\Http\Controllers\BackEnd\Organizer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Helpers\UploadFile;
use App\Models\Membership;
use App\Models\OrganizerAiBalance;
use App\Services\Ai\AiImageManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class AiImageController extends Controller
{
    private function normalizePublicImageUrl(?string $url): ?string
    {
        $url = is_string($url) ? trim($url) : '';

        if ($url === '') {
            return null;
        }

        $copiedName = UploadFile::storeFromSource(
            public_path('assets/img/ai/generated/'),
            $url
        );

        if ($copiedName) {
            return asset('assets/img/ai/generated/' . $copiedName);
        }

        return $url;
    }

    private function getCurrentMembership(int $userId, $engine): ?OrganizerAiBalance
    {
       
        return OrganizerAiBalance::query()->where([
            ['organizer_id', $userId],
            ['ai_engine', '=', $engine]
        ])->first();
    }

    private function getRemainingImageBalance(?OrganizerAiBalance $organizerAiBalance): int
    {
        if (!$organizerAiBalance) {
            return 0;
        }

        return max(
            0,
            (int) $organizerAiBalance->ai_image_balance - (int) $organizerAiBalance->total_ai_image_used
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

    private function buildImageLimitMessage(OrganizerAiBalance $organizerAiBalance, int $requestedCount = 1): string
    {
        $remaining = $this->getRemainingImageBalance($organizerAiBalance);
        $engineLabel = $this->formatEngineLabel((string) $organizerAiBalance->ai_engine);

        if ($remaining <= 0) {
            return "Your {$engineLabel} AI image generation limit has been reached. Please purchase a new package to continue.";
        }

        if ($requestedCount > $remaining) {
            $imageWord = $remaining === 1 ? 'image' : 'images';
            return "You can generate only {$remaining} more {$imageWord} with {$engineLabel}. Please reduce the count or purchase a new package.";
        }

        return '';
    }

    private function incrementUsedImages(?OrganizerAiBalance $organizerAiBalance, int $count): void
    {
        if (!$organizerAiBalance || $count <= 0) {
            return;
        }

        OrganizerAiBalance::query()
            ->where('id', $organizerAiBalance->id)
            ->where('organizer_id', $organizerAiBalance->organizer_id)
            ->where('ai_engine', $organizerAiBalance->ai_engine)
            ->increment('total_ai_image_used', $count);
    }

    public function generateImage(Request $request, AiImageManager $manager)
    {
        
        $validator = Validator::make($request->all(), [
            'prompt'   => 'required|string|min:3|max:800',
            'style'    => 'nullable|string|max:50',
            'lighting' => 'nullable|string|max:50',
            'angle'    => 'nullable|string|max:50',
            'size'     => 'nullable|string|max:50',
            'engine'   => 'required|in:pollinations,openai,gemini',
        ], [
            'engine.required' => 'Please select an AI engine before generating an image.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $organizer = Auth::guard('organizer')->user();

            $currentMembership = $this->getCurrentMembership($organizer->id, $request->engine);
            if (!$currentMembership) {
                return response()->json([
                    'status' => false,
                    'message' => 'No AI image balance was found for the selected engine.'
                ], 422);
            }

            $limitMessage = $this->buildImageLimitMessage($currentMembership, 1);
            if ($limitMessage !== '') {
                return response()->json([
                    'status' => false,
                    'message' => $limitMessage
                ], 422);
            }

            $engine = $currentMembership->ai_engine;
            // $engine = 'pollinations' ;

            $url = $manager->generateAndStore(
                $request->only('prompt', 'style', 'lighting', 'angle', 'size'),
                $engine
            );
            $url = $this->normalizePublicImageUrl($url);

            if (!empty($url)) {
                $this->incrementUsedImages($currentMembership, 1);
            }

            return response()->json([
                'status' => true,
                'image_url' => $url
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Image generation failed. Please try again.'
            ], 500);
        }
    }

    public function generateSliderImages(Request $request, AiImageManager $manager)
    {
        $validator = Validator::make($request->all(), [
            'prompt'   => 'required|string|min:3|max:800',
            'count'    => 'required|integer|min:1|max:10',

            'style'    => 'nullable|string|max:50',
            'lighting' => 'nullable|string|max:50',
            'angle'    => 'nullable|string|max:50',
            'size'     => 'nullable|string|max:50',
            'engine'   => 'required|in:pollinations,openai,gemini',
        ], [
            'engine.required' => 'Please select an AI engine before generating images.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $organizer = Auth::guard('organizer')->user();

            $currentMembership = $this->getCurrentMembership($organizer->id, $request->engine);
            if (!$currentMembership) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No AI image balance was found for the selected engine.'
                ], 422);
            }

            $requestedCount = (int) $request->count;
            $limitMessage = $this->buildImageLimitMessage($currentMembership, $requestedCount);
            if ($limitMessage !== '') {
                return response()->json([
                    'status'  => false,
                    'message' => $limitMessage
                ], 422);
            }

            $engine = $currentMembership->ai_engine;
            // $engine = 'pollinations';

            $payload = $request->only('prompt', 'style', 'lighting', 'angle', 'size');

            $count = $requestedCount;
            $images = [];
            $fails = 0;

            for ($i = 0; $i < $count; $i++) {
                try {

                    $url = $manager->generateAndStore($payload, $engine);
                    $url = $this->normalizePublicImageUrl($url);

                    if (!empty($url)) {
                        $images[] = $url;
                    } else {
                        $fails++;
                    }
                } catch (\Throwable $e) {
                    $fails++;
                }
            }

            if (count($images) === 0) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Image generation failed. Please try again.'
                ], 500);
            }

            $this->incrementUsedImages($currentMembership, count($images));

            return response()->json([
                'status' => true,
                'images' => $images,
                'failed' => $fails
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Image generation failed. Please try again.'
            ], 500);
        }
    }
}
