<?php

namespace App\Http\Middleware;

use App\Models\OrganizerAiBalance;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureOrganizerAiQuotaAvailable
{
    public function handle(Request $request, Closure $next)
    {
        $organizer = Auth::guard('organizer')->user();

        if (!$organizer) {
            return response()->json([
                'status' => false,
                'message' => 'Please log in as an organizer to use AI features.',
            ], 401);
        }

        $engine = strtolower(trim((string) $request->input('engine', '')));

        if ($engine === '' || $engine === 'pollinations') {
            return $next($request);
        }

        $balance = OrganizerAiBalance::query()
            ->where('organizer_id', $organizer->id)
            ->where('ai_engine', $engine)
            ->first();

        if (!$balance) {
            return response()->json([
                'status' => false,
                'message' => $this->missingBalanceMessage($request),
            ], 422);
        }

        if ($request->routeIs('organizer.ai.generate.content')) {
            $remainingTokens = max(0, (int) $balance->ai_token_balance - (int) $balance->total_ai_token_used);

            if ($remainingTokens <= 0) {
                return response()->json([
                    'status' => false,
                    'message' => "Your {$this->formatEngineLabel($engine)} AI content token limit has been reached. Please purchase a new package to continue.",
                ], 422);
            }
        }

        if ($request->routeIs('organizer.ai.generate.category.image') || $request->routeIs('organizer.ai.generate.slider.images')) {
            $remainingImages = max(0, (int) $balance->ai_image_balance - (int) $balance->total_ai_image_used);
            $requestedImages = $request->routeIs('organizer.ai.generate.slider.images')
                ? max(1, (int) $request->input('count', 1))
                : 1;

            if ($remainingImages <= 0) {
                return response()->json([
                    'status' => false,
                    'message' => "Your {$this->formatEngineLabel($engine)} AI image generation limit has been reached. Please purchase a new package to continue.",
                ], 422);
            }

            if ($request->routeIs('organizer.ai.generate.slider.images') && $remainingImages < $requestedImages) {
                $imageWord = $remainingImages === 1 ? 'image' : 'images';

                return response()->json([
                    'status' => false,
                    'message' => "You can generate only {$remainingImages} more {$imageWord} with {$this->formatEngineLabel($engine)}. Please reduce the count or purchase a new package.",
                ], 422);
            }
        }

        return $next($request);
    }

    private function missingBalanceMessage(Request $request): string
    {
        if ($request->routeIs('organizer.ai.generate.content')) {
            return 'No AI content token balance was found for the selected engine.';
        }

        return 'No AI image balance was found for the selected engine.';
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
}
