<?php

namespace App\Services\Ai\Contracts;

interface AiTextEngineInterface
{
  public function key(): string;

  /**
   * Should return raw text response from engine.
   */
  public function generate(string $prompt): string;
}
