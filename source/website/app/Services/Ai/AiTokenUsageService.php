<?php

namespace App\Services\Ai;

use App\Models\OrganizerAiBalance;

class AiTokenUsageService
{
    public function shouldCount(?string $engine): bool
    {
        return strtolower((string) $engine) !== 'pollinations';
    }

    public function estimateTokens(string $text): int
    {
        $text = trim($text);
        if ($text === '') {
            return 0;
        }

        $length = mb_strlen($text, 'UTF-8');
        return (int) max(1, (int) ceil($length / 4));
    }

    public function estimatePromptAndResponseTokens(string $prompt, string $response): int
    {
        return $this->estimateTokens($prompt) + $this->estimateTokens($response);
    }

    public function totalFromUsage($usage): ?int
    {
        if (!is_array($usage) || !isset($usage['total_tokens'])) {
            return null;
        }

        $total = (int) $usage['total_tokens'];
        return $total > 0 ? $total : null;
    }

    public function recordUsage(?OrganizerAiBalance $membership, ?string $engine, int $tokens): void
    {
        if (!$membership || !$this->shouldCount($engine)) {
            return;
        }

        if ($tokens <= 0) {
            return;
        }

        OrganizerAiBalance::query()
            ->where('id', $membership->id)
            ->where('organizer_id', $membership->organizer_id)
            ->where('ai_engine', $membership->ai_engine)
            ->increment('total_ai_token_used', $tokens);
    }
}
