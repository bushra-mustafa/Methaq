<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\Models\Template;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Stores the preview an administrator captured in the studio. The browser renders the card with the
 * Methaq watermark already burned in; the server accepts only a real 540x960 PNG, re-encodes it
 * (no metadata) and writes smaller WebP/AVIF twins where the server can encode them. The files live
 * on the public disk so a deploy never overwrites them.
 */
final class UpdateTemplateThumbnailAction
{
    public const DISK = 'public';

    public const WIDTH = 540;

    public const HEIGHT = 960;

    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(User $admin, Template $template, UploadedFile $preview): Template
    {
        Gate::forUser($admin)->authorize('design', $template);
        $image = $this->decode($preview);
        $stem = 'editor/templates/'.$template->slug.'-'.Str::lower(Str::random(8));
        $written = [];

        try {
            $written[] = $this->store($stem.'.png', $image, 'png');
            if (function_exists('imagewebp')) {
                $written[] = $this->store($stem.'.webp', $image, 'webp');
            }
            if (function_exists('imageavif')) {
                $written[] = $this->store($stem.'.avif', $image, 'avif');
            }
            imagedestroy($image);

            return DB::transaction(function () use ($admin, $template, $stem): Template {
                $locked = Template::query()->lockForUpdate()->findOrFail($template->getKey());
                $previous = (string) $locked->thumbnail_path;
                $locked->forceFill(['thumbnail_path' => 'storage/'.$stem.'.png'])->save();
                $this->forget($previous);
                $this->auditLogger->record(
                    actor: $admin,
                    action: AuditAction::TemplateThumbnailUpdated,
                    subjectType: AuditSubjectType::Template,
                    subjectId: (int) $locked->getKey(),
                    reason: null,
                    metadata: ['path' => 'storage/'.$stem.'.png'],
                );

                return $locked;
            });
        } catch (Throwable $exception) {
            foreach ($written as $path) {
                Storage::disk(self::DISK)->delete($path);
            }

            throw $exception;
        }
    }

    private function decode(UploadedFile $preview): \GdImage
    {
        $invalid = ValidationException::withMessages(['preview' => 'صورة المعاينة غير صالحة. التقطيها من المحرر مرة أخرى.']);
        $path = (string) $preview->getRealPath();
        $info = @getimagesize($path);
        if ($info === false || $info[2] !== IMAGETYPE_PNG || $info[0] !== self::WIDTH || $info[1] !== self::HEIGHT) {
            throw $invalid;
        }

        $image = @imagecreatefrompng($path);
        if ($image === false) {
            throw $invalid;
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $image;
    }

    private function store(string $path, \GdImage $image, string $format): string
    {
        ob_start();
        $encoded = match ($format) {
            'webp' => imagewebp($image, null, 82),
            'avif' => imageavif($image, null, 60),
            default => imagepng($image, null, 6),
        };
        $bytes = (string) ob_get_clean();
        if (! $encoded || $bytes === '' || ! Storage::disk(self::DISK)->put($path, $bytes)) {
            throw new RuntimeException('The template preview could not be stored.');
        }

        return $path;
    }

    /** Removes a previous studio preview and its twins; shipped previews under public/editor are never touched. */
    private function forget(string $thumbnailPath): void
    {
        if (! str_starts_with($thumbnailPath, 'storage/editor/templates/') || ! str_ends_with($thumbnailPath, '.png')) {
            return;
        }

        $stem = substr($thumbnailPath, strlen('storage/'), -4);
        foreach (['png', 'webp', 'avif'] as $extension) {
            Storage::disk(self::DISK)->delete($stem.'.'.$extension);
        }
    }
}
