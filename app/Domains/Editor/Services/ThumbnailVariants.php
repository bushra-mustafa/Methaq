<?php

declare(strict_types=1);

namespace App\Domains\Editor\Services;

/**
 * Preview thumbnails ship as PNG with smaller WebP and AVIF twins beside them. Only these public
 * previews have twins: owner artwork, editor assets and exports are never converted here.
 */
final class ThumbnailVariants
{
    /** The public URL of the WebP twin of a PNG thumbnail, or null when no twin exists. */
    public static function webpUrl(?string $thumbnailPath): ?string
    {
        return self::twin($thumbnailPath, 'webp');
    }

    /** The public URL of the AVIF twin of a PNG thumbnail, or null when no twin exists. */
    public static function avifUrl(?string $thumbnailPath): ?string
    {
        return self::twin($thumbnailPath, 'avif');
    }

    private static function twin(?string $thumbnailPath, string $extension): ?string
    {
        if ($thumbnailPath === null || ! str_ends_with($thumbnailPath, '.png')) {
            return null;
        }

        $twin = substr($thumbnailPath, 0, -4).'.'.$extension;

        return is_file(public_path($twin)) ? '/'.ltrim($twin, '/') : null;
    }
}
