<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiImageEngineInterface;
use App\Services\Ai\Engines\GeminiImageEngine;
use App\Services\Ai\Engines\OpenAiImageEngine;
use App\Services\Ai\Engines\PollinationsImageEngine;

class AiImageManager
{
  public function engine(string $engine): AiImageEngineInterface
  {
    return match ($engine) {
      'openai' => app(OpenAiImageEngine::class),
      'gemini' => app(GeminiImageEngine::class),
      default  => app(PollinationsImageEngine::class),
    };
  }

  public function generateAndStore(array $data, string $engine): string
  {
    return $this->engine($engine)->generateAndStore($data);
  }
}
