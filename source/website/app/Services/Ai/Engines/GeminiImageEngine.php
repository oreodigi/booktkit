<?php

namespace App\Services\Ai\Engines;

use App\Services\Ai\Contracts\AiImageEngineInterface;
use App\Services\Ai\Engines\Concerns\BuildsImagePrompt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GeminiImageEngine implements AiImageEngineInterface
{
  use BuildsImagePrompt;

  public function generateAndStore(array $data): string
  {
    $apiKey = (string) config('ai.gemini_api_key', '');
    if ($apiKey === '') {
      throw new \RuntimeException('GEMINI_API_KEY missing');
    }

    $model = (string) config('ai.gemini_image_model', 'gemini-3.1-flash-image');

    $prompt = trim((string)($data['prompt'] ?? ''));
    if ($prompt === '') {
      throw new \InvalidArgumentException('Prompt is required');
    }

    $finalPrompt = $this->buildPrompt(
      $prompt,
      (string)($data['style'] ?? ''),
      (string)($data['lighting'] ?? ''),
      (string)($data['angle'] ?? '')
    );

    [$w, $h] = $this->resolveSize((string)($data['size'] ?? 'square_1024'));
    $aspectRatio = $this->aspectRatioFromSize($w, $h); 

    $endpoint = "https://generativelanguage.googleapis.com/v1beta/interactions";

    try {
      $response = Http::timeout(180)
        ->withHeaders(['x-goog-api-key' => $apiKey])
        ->acceptJson()
        ->post($endpoint, [
          'model' => $model,
          'input' => [['type' => 'text', 'text' => $finalPrompt]],
          'response_format' => ['type' => 'image', 'mime_type' => 'image/jpeg', 'aspect_ratio' => $aspectRatio, 'image_size' => '1K'],
        ]);

      if (!$response->successful()) {
        \Log::warning('Gemini image generate failed', [
          'status' => $response->status(),
          'body' => $response->body(),
        ]);
        throw new \RuntimeException('Gemini did not return a usable generated image.');
      }

      $json = $response->json();
      // Interactions REST wraps the generated output inside the interaction object.
      $base64 = $json['interaction']['output_image']['data'] ?? $json['output_image']['data'] ?? null;
      if (!$base64 && !empty($json['interaction']['steps'])) {
        foreach ($json['interaction']['steps'] as $step) {
          foreach (($step['content'] ?? []) as $block) {
            if (($block['type'] ?? null) === 'image' && !empty($block['data'])) { $base64 = $block['data']; break 2; }
          }
        }
      }

      if (!$base64) {
        throw new \RuntimeException('Gemini did not return a usable generated image.');
      }

      $imageBinary = base64_decode($base64, true);
      if ($imageBinary === false) {
        throw new \RuntimeException('Gemini did not return a usable generated image.');
      }

      Storage::disk('public')->makeDirectory('ai/categories');

      $filename = 'gemini_' . now()->format('Ymd_His') . '_' . Str::random(8) . '.jpg';
      $path = 'ai/categories/' . $filename;

      $saved = Storage::disk('public')->put($path, $imageBinary); 
      if (!$saved) {
        throw new \RuntimeException('Failed to save generated image');
      }

      $this->resizeStoredImage($path, $w, $h);

      return '/storage/' . $path;
    } catch (\Throwable $e) {
      \Log::error('Gemini image engine exception', [
        'error' => $e->getMessage(),
      ]);
      throw new \RuntimeException('Gemini did not return a usable generated image.');
    }
  }


  //aspectratio generate for gemini
  private function aspectRatioFromSize($width, $height)
  {
    if (!$width || !$height) return '1:1';

    $ratio = $width / $height;

    if (abs($ratio - 1) < 0.1) return '1:1';
    if ($ratio > 1.7) return '16:9';
    if ($ratio > 1.2) return '4:3';
    if ($ratio < 0.6) return '9:16';

    return '3:4';
  }
}
