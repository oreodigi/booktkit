<?php

namespace App\Support;

/**
 * Remembers which not-yet-attached gallery uploads belong to the current browser session, so an
 * upload can only be attached to (or removed from) an event by whoever uploaded it.
 */
class GalleryUploadRegistry
{
    private const KEY = 'booktkit_gallery_uploads';

    public static function remember(int $imageId): void
    {
        if (!app()->bound('session')) return;
        $ids = array_slice(array_values(array_unique(array_merge((array) session(self::KEY, []), [$imageId]))), -200);
        session([self::KEY => $ids]);
    }

    public static function owns(int $imageId): bool
    {
        return app()->bound('session') && in_array($imageId, array_map('intval', (array) session(self::KEY, [])), true);
    }

    /** @param array<int|string> $ids */
    public static function filter(array $ids): array
    {
        if (!app()->bound('session')) return [];
        return array_values(array_filter(array_map('intval', $ids), fn ($id) => self::owns($id)));
    }
}
