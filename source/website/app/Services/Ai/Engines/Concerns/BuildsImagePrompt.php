<?php

namespace App\Services\Ai\Engines\Concerns;

use Illuminate\Support\Str;

trait BuildsImagePrompt
{
  protected function resolveSize(string $size): array
  {
    // Allow custom sizes from UI: custom_{width}_{height}
    if (preg_match('/^custom_(\d{2,4})_(\d{2,4})$/', $size, $m)) {
      return [(int) $m[1], (int) $m[2]];
    }

    return match ($size) {
      'portrait_1024_1536'  => [1024, 1536],
      'landscape_1536_1024' => [1536, 1024],
      default               => [1024, 1024],
    };
  }

  protected function buildPrompt(string $prompt, string $style, string $lighting, string $angle): string
  {
    $chunks = [$prompt];

    $styleMap = [
      'photorealistic'    => 'photorealistic',
      '3d_render'         => '3d render',
      'flat_illustration' => 'flat illustration',
      'minimal'           => 'minimal',
    ];
    $lightingMap = [
      'natural'  => 'natural light',
      'studio'   => 'studio lighting',
      'soft'     => 'soft light',
      'dramatic' => 'dramatic lighting',
    ];
    $angleMap = [
      'eye_level' => 'eye-level',
      'top_down'  => 'top-down',
      'close_up'  => 'close-up',
      'wide'      => 'wide shot',
    ];

    if (isset($styleMap[$style])) $chunks[] = $styleMap[$style];
    if (isset($lightingMap[$lighting])) $chunks[] = $lightingMap[$lighting];
    if (isset($angleMap[$angle])) $chunks[] = $angleMap[$angle];

    $chunks[] = 'high quality, clean background, product thumbnail';

    return implode(', ', $chunks);
  }

  protected function resizeStoredImage(string $storagePath, int $width, int $height): void
  {
    if ($width <= 0 || $height <= 0) {
      return;
    }

    $relative = ltrim($storagePath, '/');
    $absPath = storage_path('app/public/' . $relative);

    if (!file_exists($absPath)) {
      return;
    }

    try {
      [$currentWidth, $currentHeight] = @getimagesize($absPath) ?: [0, 0];
      if ((int) $currentWidth === $width && (int) $currentHeight === $height) {
        return;
      }

      $this->resizeImageFile($absPath, $absPath, $width, $height);
    } catch (\Throwable $e) {
      // If resize fails, keep the original image.
    }
  }

  protected function storageBase(string $prefix): string
  {
    return 'ai/categories/' . $prefix . now()->format('Ymd_His') . '_' . Str::random(8);
  }

  protected function resizeImageFile(
    string $sourcePath,
    string $destinationPath,
    int $targetWidth,
    int $targetHeight,
    bool $crop = false,
    int $quality = 90
  ): bool {
    if (!is_file($sourcePath) || $targetWidth <= 0 || $targetHeight <= 0) {
      return false;
    }

    $imageInfo = @getimagesize($sourcePath);
    if (!$imageInfo || empty($imageInfo['mime'])) {
      return false;
    }

    $binary = @file_get_contents($sourcePath);
    if ($binary === false) {
      return false;
    }

    $sourceImage = @imagecreatefromstring($binary);
    if (!$sourceImage) {
      return false;
    }

    $srcWidth = imagesx($sourceImage);
    $srcHeight = imagesy($sourceImage);

    $srcX = 0;
    $srcY = 0;
    $srcCropWidth = $srcWidth;
    $srcCropHeight = $srcHeight;
    $destWidth = $targetWidth;
    $destHeight = $targetHeight;

    if ($crop) {
      $sourceRatio = $srcWidth / max($srcHeight, 1);
      $targetRatio = $targetWidth / max($targetHeight, 1);

      if ($sourceRatio > $targetRatio) {
        $srcCropWidth = (int) round($srcHeight * $targetRatio);
        $srcX = (int) floor(($srcWidth - $srcCropWidth) / 2);
      } else {
        $srcCropHeight = (int) round($srcWidth / $targetRatio);
        $srcY = (int) floor(($srcHeight - $srcCropHeight) / 2);
      }
    } else {
      $scale = min($targetWidth / max($srcWidth, 1), $targetHeight / max($srcHeight, 1));
      if ($scale <= 0) {
        imagedestroy($sourceImage);
        return false;
      }

      $destWidth = max(1, (int) round($srcWidth * $scale));
      $destHeight = max(1, (int) round($srcHeight * $scale));
    }

    $canvas = imagecreatetruecolor($destWidth, $destHeight);
    if (!$canvas) {
      imagedestroy($sourceImage);
      return false;
    }

    if (in_array($imageInfo['mime'], ['image/png', 'image/gif', 'image/webp'], true)) {
      imagealphablending($canvas, false);
      imagesavealpha($canvas, true);
      $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
      imagefilledrectangle($canvas, 0, 0, $destWidth, $destHeight, $transparent);
    }

    $resampled = imagecopyresampled(
      $canvas,
      $sourceImage,
      0,
      0,
      $srcX,
      $srcY,
      $destWidth,
      $destHeight,
      $srcCropWidth,
      $srcCropHeight
    );

    if (!$resampled) {
      imagedestroy($canvas);
      imagedestroy($sourceImage);
      return false;
    }

    $extension = strtolower((string) pathinfo($destinationPath, PATHINFO_EXTENSION));
    $saved = match ($extension) {
      'png' => imagepng($canvas, $destinationPath),
      'gif' => imagegif($canvas, $destinationPath),
      'webp' => function_exists('imagewebp') ? imagewebp($canvas, $destinationPath, $quality) : imagepng($canvas, $destinationPath),
      default => imagejpeg($canvas, $destinationPath, $quality),
    };

    imagedestroy($canvas);
    imagedestroy($sourceImage);

    return (bool) $saved;
  }
}
