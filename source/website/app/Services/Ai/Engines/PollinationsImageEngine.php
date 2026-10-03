<?php

namespace App\Services\Ai\Engines;

use App\Services\Ai\Contracts\AiImageEngineInterface;
use App\Services\Ai\Engines\Concerns\BuildsImagePrompt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PollinationsImageEngine implements AiImageEngineInterface
{
  use BuildsImagePrompt;

  private string $base = 'https://gen.pollinations.ai/';

  public function generateAndStore(array $data): string
  {
    $prompt = trim((string)($data['prompt'] ?? ''));
    if ($prompt === '') {
      throw new \InvalidArgumentException('Prompt is required');
    }

    // Build final prompt from selects 
    $finalPrompt = $this->buildPrompt(
      $prompt,
      (string)($data['style'] ?? ''),
      (string)($data['lighting'] ?? ''),
      (string)($data['angle'] ?? '')
    );

    // UI size -> width/height
    [$w, $h] = $this->resolveSize((string)($data['size'] ?? 'square_1024'));

    // Pollinations params
    $apiKey = (string) config('ai.pollinations_secret_key', '');
    $model = (string) config('ai.pollinations_image_model', 'flux');
    $params = [
      'model'  =>  $model,
      'width'  => $w,
      'height' => $h,
      'nologo' => 'true',
      'private' => 'false',
      'seed'   => random_int(1000, 999999),
    ];

    $url = $this->base . 'image/' . rawurlencode($finalPrompt);

    // HTTP request with optional API key
    $http = \Illuminate\Support\Facades\Http::timeout(120);

    // the authorization header is added if an API key is available
    if (!empty($apiKey)) {
      $http = $http->withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
      ]);
    }

    $resp = $http->get($url, $params);

    if (!$resp->ok()) {
      throw new \RuntimeException('Pollinations request failed');
    }

    // Determine extension from Content-Type
    $contentType = (string) $resp->header('Content-Type', '');
    $ext = str_contains($contentType, 'png') ? 'png' : 'jpg';

    // Ensure directory exists
    \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('ai/categories');

    // Store file
    $filename = 'poll_' . now()->format('Ymd_His') . '_' . \Illuminate\Support\Str::random(8) . '.' . $ext;
    $path = 'ai/categories/' . $filename;

    $saved = \Illuminate\Support\Facades\Storage::disk('public')->put($path, $resp->body());
    if (!$saved) {
      throw new \RuntimeException('Failed to save generated image');
    }

    $this->resizeStoredImage($path, $w, $h);

    return '/storage/' . $path;
  }
}
