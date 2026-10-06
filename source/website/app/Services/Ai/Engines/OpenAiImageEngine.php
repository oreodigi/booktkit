<?php

namespace App\Services\Ai\Engines;

use App\Services\Ai\Contracts\AiImageEngineInterface;
use App\Services\Ai\Engines\Concerns\BuildsImagePrompt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class OpenAiImageEngine implements AiImageEngineInterface
{
  use BuildsImagePrompt;


  public function generateAndStore(array $data): string
  {
    $apiKey = (string) config('ai.openai_api_key', '');
    if ($apiKey === '') {
      throw new \RuntimeException('OPENAI_API_KEY missing');
    }

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

    [$targetW, $targetH] = $this->resolveSize((string)($data['size'] ?? 'square_1024'));

    $model = (string) config('ai.openai_image_model', 'gpt-image-1.5');
    [$apiW, $apiH] = $this->normalizeOpenAiSize($targetW, $targetH, $model);
    $sizeStr = $apiW . 'x' . $apiH;

    $payload = [
      'model'  => $model,
      'prompt' => $finalPrompt,
      'n'      => 1,
      'size'   => $sizeStr,
      'quality' => 'low',
      'output_format' => 'jpeg',
     ];

    try {
      $resp = Http::timeout(120)
        ->withToken($apiKey)
        ->acceptJson()
        ->post('https://api.openai.com/v1/images/generations', $payload);

      if (!$resp->successful()) {
        \Log::warning('OpenAI image generate failed', [
          'status' => $resp->status(),
          'body'   => $resp->body(),
          'json'   => $resp->json(),   
          'model'  => $model,
          'size'   => $sizeStr,
          
          'prompt_snippet' => mb_substr($finalPrompt, 0, 200),
        ]);

        return '';
      }

      $json = $resp->json();

      $b64 = $json['data'][0]['b64_json'] ?? null;
      $remoteUrl = $json['data'][0]['url'] ?? null;

      if (!$b64 && $remoteUrl) {
        $download = Http::timeout(120)->get($remoteUrl);
        if ($download->successful()) $b64 = base64_encode($download->body());
      }

      if (!$b64) {
        \Log::warning('OpenAI image response missing b64_json', [
          'model' => $model,
          'size'  => $sizeStr,
          'keys'  => array_keys($json ?? []),
          'first_data_keys' => isset($json['data'][0]) ? array_keys($json['data'][0]) : null,
        ]);
        return '';
      }

      $bytes = base64_decode($b64, true);
      if ($bytes === false) {
        \Log::warning('OpenAI image base64 decode failed', [
          'model' => $model,
          'size'  => $sizeStr,
        ]);
        return '';
      }

      $path = $this->storageBase('oa_') . '.jpg';
      Storage::disk('public')->put($path, $bytes);

      $this->resizeStoredImage($path, $targetW, $targetH);

      return '/storage/' . $path;
    } catch (\Throwable $e) {
      \Log::error('OpenAI image engine exception', [
        'error' => $e->getMessage(),
        'model' => $model,
        'size'  => $sizeStr,
      ]);
      return '';
    }
  }

  private function normalizeOpenAiSize(int $w, int $h, string $model): array
  {
    $model = strtolower(trim($model));

    // GPT Image supports 1024x1024, 1536x1024, 1024x1536
    if (str_contains($model, 'gpt-image')) {
      if ($w > $h) return [1536, 1024];
      if ($h > $w) return [1024, 1536];
      return [1024, 1024];
    }

    // DALL-E 3 supports 1024x1024, 1024x1792, 1792x1024
    if (str_contains($model, 'dall-e-3')) {
      if ($w > $h) return [1792, 1024];
      if ($h > $w) return [1024, 1792];
      return [1024, 1024];
    }

    // DALL-E 2 supports square sizes only: 256, 512, 1024
    return [1024, 1024];
  }
}
