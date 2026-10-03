<?php

namespace App\Services\Ai\Engines;

use App\Services\Ai\Contracts\AiTextEngineInterface;
use Illuminate\Support\Facades\Http;

class PollinationsTextEngine implements AiTextEngineInterface
{
  public function key(): string
  {
    return 'pollinations';
  }

  public function generate(string $prompt): string
  {

    $url = 'https://gen.pollinations.ai/v1/chat/completions';
    $apiKey = (string) config('ai.pollinations_secret_key', '');
    $model = (string) config('ai.pollinations_text_model', 'openai');

    $payload = [
      'model' => $model,
      'messages' => [
        [
          'role' => 'user',
          'content' => $prompt
        ],
      ],
      'temperature' => 0.4,
    ];

    $headers = [
      'Content-Type'  => 'application/json',
    ];
    if ($apiKey !== '') {
      $headers['Authorization'] = 'Bearer ' . $apiKey;
    }

    $response = Http::timeout((int) config('ai.http_timeout', 90))
      ->connectTimeout(20)
      ->retry(2, 1500)
      ->withHeaders($headers)
      ->post($url, $payload);

    if (!$response->successful()) {
      throw new \RuntimeException('Pollinations failed: ' . $response->body());
    }

    $json = $response->json();
    $content = data_get($json, 'choices.0.message.content');

    if (is_string($content) && trim($content) !== '') {
      return trim($content); 
    }

    return trim($response->body());
  }
}
