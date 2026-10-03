<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiTextEngineInterface;
use App\Services\Ai\Engines\GeminiTextEngine;
use App\Services\Ai\Engines\OpenAiTextEngine;
use App\Services\Ai\Engines\PollinationsTextEngine;

class AiTextManager
{
  /** @var array<string, AiTextEngineInterface> */
  private array $engines;

  public function __construct(
    PollinationsTextEngine $pollinations,
    OpenAiTextEngine $openai,
    GeminiTextEngine $gemini,
  ) {
    $this->engines = [
      $pollinations->key() => $pollinations,
      $openai->key() => $openai,
      $gemini->key() => $gemini,
    ];
  }

  public function engine(?string $key = null): AiTextEngineInterface
  {
    $key = $key ?: config('ai.default_text_engine', 'openai');

    if (!isset($this->engines[$key])) {
      // fallback to default
      $fallback = config('ai.default_text_engine', 'openai');
      return $this->engines[$fallback];
    }

    return $this->engines[$key];
  }

  public function generate(string $prompt, ?string $engineKey = null): string
  {
    return $this->engine($engineKey)->generate($prompt);
  }

  public function generateWithMeta(string $prompt, ?string $engineKey = null): array
  {
    $engine = $this->engine($engineKey);

    if (method_exists($engine, 'generateWithMeta')) {
      return $engine->generateWithMeta($prompt);
    }

    return [
      'text' => $engine->generate($prompt),
      'usage' => null,
    ];
  }
}
