<?php

namespace App\Services\Ai;

class ContentGenerator
{
  public function buildPrompt(string $idea, array $targets): string
  {
    $langList = implode(', ', array_map(fn($t) => "{$t['code']} ({$t['name']})", $targets));
    $codes = implode(', ', array_map(fn($t) => $t['code'], $targets));

    return
      "Return ONLY valid JSON object.\n" .
      "No markdown, no extra text.\n" .
      "Keys must be EXACT language codes: {$codes}\n" .
      "Values must be short category names (1-4 words).\n\n" .
      "Category idea: {$idea}\n" .
      "Languages: {$langList}\n" .
      "Return JSON like: {\"en\":\"...\",\"bn\":\"...\"}\n";
  }

  public function extractJson($text): array
  {
    // if engine already returns array
    if (is_array($text)) return $text;

    $text = trim((string) $text);

    // first try decode whole text
    $decoded = json_decode($text, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
      return $decoded;
    }

    // fallback: extract {...} slice
    $start = strpos($text, '{');
    $end   = strrpos($text, '}');

    if ($start === false || $end === false || $end <= $start) return [];

    $slice = substr($text, $start, $end - $start + 1);
    $decoded = json_decode($slice, true);

    return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
  }


  public function fallbackName(string $idea): string
  {
    $idea = trim($idea);
    $words = preg_split('/\s+/', $idea);
    $words = array_slice($words, 0, 4);
    $name = implode(' ', $words);
    $name = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $name);
    $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    return $name ?: 'New Category';
  }
}
