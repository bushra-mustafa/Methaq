<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Services\ThumbnailVariants;
use Tests\TestCase;

final class ThumbnailVariantsTest extends TestCase
{
    public function test_a_png_preview_with_a_webp_twin_exposes_the_twin_and_everything_else_does_not(): void
    {
        $this->assertSame('/editor/previews/templates/powder-gold.webp', ThumbnailVariants::webpUrl('editor/previews/templates/powder-gold.png'));
        $this->assertSame('/editor/previews/templates/powder-gold.avif', ThumbnailVariants::avifUrl('editor/previews/templates/powder-gold.png'));
        $this->assertNull(ThumbnailVariants::webpUrl('editor/previews/templates/henna-noir.svg'));
        $this->assertNull(ThumbnailVariants::avifUrl('editor/previews/templates/henna-noir.svg'));
        $this->assertNull(ThumbnailVariants::webpUrl('editor/previews/templates/does-not-exist.png'));
        $this->assertNull(ThumbnailVariants::webpUrl(null));
    }

    public function test_every_template_and_collection_png_preview_has_a_smaller_twin(): void
    {
        $previews = [
            ...(glob(public_path('editor/previews/templates/*.png')) ?: []),
            ...(glob(public_path('editor/previews/collections/v1/*.png')) ?: []),
        ];

        $this->assertNotEmpty($previews);

        foreach ($previews as $png) {
            foreach (['webp', 'avif'] as $extension) {
                $twin = substr($png, 0, -4).'.'.$extension;

                $this->assertFileExists($twin, basename($png)." has no {$extension} twin; run the conversion in docs/deployment.md");
                $this->assertLessThan(filesize($png), filesize($twin), basename($twin).' is not smaller than its PNG');
            }
        }
    }
}
