<?php

namespace App\Services\Ai\Contracts;

interface AiImageEngineInterface
{
  public function generateAndStore(array $data): string;
}
