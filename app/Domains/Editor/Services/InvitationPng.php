<?php

declare(strict_types=1);

namespace App\Domains\Editor\Services;

use App\Domains\Editor\Contracts\DesignSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final class InvitationPng
{
    /** Accepts only a real 1080x1920 PNG and returns it re-encoded without any embedded metadata. */
    public function normalise(UploadedFile $render): string
    {
        $invalid = ValidationException::withMessages(['render' => 'صورة البطاقة غير صالحة. حدّثي الصفحة وحاولي مرة أخرى.']);
        $path = (string) $render->getRealPath();
        $info = @getimagesize($path);

        if ($info === false || $info[2] !== IMAGETYPE_PNG || $info[0] !== DesignSchema::CANVAS_WIDTH || $info[1] !== DesignSchema::CANVAS_HEIGHT) {
            throw $invalid;
        }

        $image = @imagecreatefrompng($path);
        if ($image === false) {
            throw $invalid;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);
        ob_start();
        $encoded = imagepng($image, null, 6);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        if (! $encoded || $bytes === '') {
            throw $invalid;
        }

        return $bytes;
    }
}
