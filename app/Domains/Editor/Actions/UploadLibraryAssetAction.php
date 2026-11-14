<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\Enums\AssetType;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Editor\Services\SvgAssetValidator;
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
use InvalidArgumentException;
use Throwable;

/**
 * An administrator adds a design element to the library: a frame, decoration, icon, background or font.
 *
 * File types are read from the bytes, never the name. SVG artwork stays private (validated to a safe subset) and needs a
 * PNG preview, which is what customers' browsers load; pictures are re-encoded (metadata stripped, long edge capped) for the
 * public copy while the original stays private. The administrator states the source and confirms the right to use it.
 */
final class UploadLibraryAssetAction
{
    public const MAX_FILE_BYTES = 5 * 1024 * 1024;

    public const MAX_FONT_BYTES = 3 * 1024 * 1024;

    private const MAX_EDGE = 2000;

    private const MAX_PIXELS = 36_000_000;

    /** @var list<AssetType> */
    public const TYPES = [AssetType::Frame, AssetType::Decoration, AssetType::Icon, AssetType::Background, AssetType::Font];

    public function __construct(private readonly AuditLogger $auditLogger, private readonly SvgAssetValidator $svg) {}

    public function execute(
        User $admin,
        AssetType $type,
        string $name,
        UploadedFile $file,
        ?UploadedFile $preview,
        string $source,
        bool $rightsConfirmed,
        ?string $fontFamily = null,
    ): TemplateAsset {
        Gate::forUser($admin)->authorize('create', TemplateAsset::class);

        $name = trim($name);
        $source = trim($source);
        if (! in_array($type, self::TYPES, true)) {
            throw ValidationException::withMessages(['type' => 'اختاري إطاراً أو زخرفة أو أيقونة أو خلفية أو خطاً.']);
        }
        if ($name === '' || mb_strlen($name) > 120) {
            throw ValidationException::withMessages(['name' => 'اكتبي اسماً للعنصر لا يتجاوز 120 حرفاً.']);
        }
        if ($source === '' || mb_strlen($source) > 300) {
            throw ValidationException::withMessages(['source' => 'اذكري مصدر العنصر أو ترخيصه (حتى 300 حرف).']);
        }
        if (! $rightsConfirmed) {
            throw ValidationException::withMessages(['rights' => 'أكّدي أن لديك حق استخدام هذا العنصر داخل دعوات العملاء.']);
        }
        $limit = $type === AssetType::Font ? self::MAX_FONT_BYTES : self::MAX_FILE_BYTES;
        if (! $file->isValid() || $file->getSize() === false || $file->getSize() < 1 || $file->getSize() > $limit) {
            throw ValidationException::withMessages(['file' => 'الملف تالف أو يتجاوز '.($limit / 1048576).' ميغابايت.']);
        }

        $prepared = $type === AssetType::Font ? $this->prepareFont($file, $fontFamily) : $this->prepareVisual($file, $preview);
        $stem = Str::uuid()->toString();
        $originalPath = 'editor/library/'.$stem.'.'.$prepared['originalExtension'];
        $publicPath = ($type === AssetType::Font ? 'editor/library/fonts/' : 'editor/library/').$stem.'.'.$prepared['publicExtension'];

        try {
            Storage::disk('local')->put($originalPath, $prepared['originalBytes']);
            Storage::disk('public')->put($publicPath, $prepared['publicBytes']);

            return DB::transaction(function () use ($admin, $type, $name, $source, $prepared, $originalPath, $publicPath, $file): TemplateAsset {
                $isFont = $type === AssetType::Font;
                $asset = new TemplateAsset;
                $asset->forceFill([
                    'slug' => ($isFont ? 'font-up-' : 'asset-').Str::lower(Str::random(10)),
                    'name' => $name,
                    'type' => $type,
                    'original_path' => $originalPath,
                    'preview_path' => $isFont ? 'editor/previews/assets/v1/fonts/font-sample.svg' : 'storage/'.$publicPath,
                    'mime_type' => $prepared['mime'],
                    'width' => $prepared['width'],
                    'height' => $prepared['height'],
                    'metadata' => ['uploaded' => true, 'sizeBytes' => (int) $file->getSize(), ...$prepared['metadata'], ...($isFont ? ['publicPath' => 'storage/'.$publicPath] : [])],
                    'capabilities' => $prepared['capabilities'],
                    'license_metadata' => ['source' => $source, 'rightsConfirmed' => true, 'uploadedBy' => $admin->getKey()],
                    'asset_version' => 1,
                    'is_active' => true,
                ])->save();

                $this->auditLogger->record(
                    actor: $admin,
                    action: AuditAction::TemplateAssetUploaded,
                    subjectType: AuditSubjectType::TemplateAsset,
                    subjectId: (int) $asset->getKey(),
                    reason: $source,
                    metadata: ['name' => $name, 'type' => $type->value, 'mime' => $prepared['mime']],
                );

                return $asset;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($originalPath);
            Storage::disk('public')->delete($publicPath);

            throw $exception;
        }
    }

    /** @return array{originalBytes: string, originalExtension: string, publicBytes: string, publicExtension: string, mime: string, width: ?int, height: ?int, metadata: array<string, mixed>, capabilities: array<string, mixed>} */
    private function prepareVisual(UploadedFile $file, ?UploadedFile $preview): array
    {
        $bytes = (string) file_get_contents((string) $file->getRealPath());
        $mime = $this->detectedMime($file);

        if (str_contains($bytes, '<svg') && in_array($mime, ['image/svg+xml', 'text/xml', 'application/xml', 'text/plain'], true)) {
            try {
                $this->svg->assertSafe($bytes);
            } catch (InvalidArgumentException) {
                throw ValidationException::withMessages(['file' => 'ملف SVG غير صالح أو يحتوي عناصر غير مسموحة (نصوص برمجية أو صور خارجية أو أنماط).']);
            }
            if ($preview === null || ! $preview->isValid()) {
                throw ValidationException::withMessages(['preview' => 'أرفقي صورة معاينة PNG لملف SVG، فهي ما يراه العملاء داخل المحرر.']);
            }
            $image = $this->reencode($preview, 'preview');
            if ($image['mime'] !== 'image/png') {
                throw ValidationException::withMessages(['preview' => 'صورة المعاينة يجب أن تكون PNG.']);
            }
            $ratio = $this->svgRatio($bytes);
            if ($ratio !== null && abs($ratio - $image['width'] / $image['height']) / $ratio > 0.05) {
                throw ValidationException::withMessages(['preview' => 'نسبة أبعاد صورة المعاينة لا تطابق ملف SVG. صدّري المعاينة بالأبعاد نفسها.']);
            }

            return [
                'originalBytes' => $bytes, 'originalExtension' => 'svg', 'publicBytes' => $image['bytes'], 'publicExtension' => 'png',
                'mime' => 'image/svg+xml', 'width' => $image['width'], 'height' => $image['height'], 'metadata' => ['format' => 'svg'],
                'capabilities' => ['supportsTint' => true, 'supportsOpacity' => true, 'allowedPlacements' => ['canvas', 'collection']],
            ];
        }

        $image = $this->reencode($file, 'file');

        return [
            'originalBytes' => $bytes, 'originalExtension' => ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'][$mime] ?? $image['extension'],
            'publicBytes' => $image['bytes'], 'publicExtension' => $image['extension'], 'mime' => $mime,
            'width' => $image['width'], 'height' => $image['height'], 'metadata' => ['format' => $image['extension']],
            'capabilities' => ['supportsTint' => false, 'supportsOpacity' => true, 'allowedPlacements' => ['canvas', 'collection']],
        ];
    }

    /** @return array{originalBytes: string, originalExtension: string, publicBytes: string, publicExtension: string, mime: string, width: null, height: null, metadata: array<string, mixed>, capabilities: array<string, mixed>} */
    private function prepareFont(UploadedFile $file, ?string $fontFamily): array
    {
        $family = trim((string) $fontFamily);
        if (preg_match('/^[\p{L}\p{N} _-]{2,60}$/u', $family) !== 1) {
            throw ValidationException::withMessages(['fontFamily' => 'اكتبي اسم عائلة الخط بحروف وأرقام فقط (2 إلى 60).']);
        }
        if (TemplateAsset::query()->where('type', AssetType::Font->value)->where('metadata->fontFamily', $family)->exists()) {
            throw ValidationException::withMessages(['fontFamily' => 'يوجد خط بهذا الاسم. اختاري اسماً مختلفاً.']);
        }

        $bytes = (string) file_get_contents((string) $file->getRealPath());
        $format = match (substr($bytes, 0, 4)) {
            'wOF2' => ['woff2', 'font/woff2', 'woff2'],
            "\x00\x01\x00\x00", 'true' => ['ttf', 'font/ttf', 'truetype'],
            'OTTO' => ['otf', 'font/otf', 'opentype'],
            default => null,
        };
        if ($format === null) {
            throw ValidationException::withMessages(['file' => 'الملف يجب أن يكون خطاً بصيغة WOFF2 أو TTF أو OTF.']);
        }

        return [
            'originalBytes' => $bytes, 'originalExtension' => $format[0], 'publicBytes' => $bytes, 'publicExtension' => $format[0],
            'mime' => $format[1], 'width' => null, 'height' => null, 'metadata' => ['fontFamily' => $family, 'format' => $format[2]],
            'capabilities' => ['languages' => ['ar', 'en']],
        ];
    }

    /** @return array{bytes: string, extension: string, mime: string, width: int, height: int} */
    private function reencode(UploadedFile $file, string $field): array
    {
        $path = (string) $file->getRealPath();
        $mime = $this->detectedMime($file);
        $info = in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true) ? @getimagesize($path) : false;
        if ($info === false || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > self::MAX_PIXELS || $file->getSize() > self::MAX_FILE_BYTES) {
            throw ValidationException::withMessages([$field => 'الصورة يجب أن تكون PNG أو JPG أو WebP سليمة وبحجم معقول.']);
        }

        $source = @imagecreatefromstring((string) file_get_contents($path));
        if ($source === false) {
            throw ValidationException::withMessages([$field => 'تعذّر قراءة الصورة.']);
        }

        $width = $info[0];
        $height = $info[1];
        $scale = min(1.0, self::MAX_EDGE / max($width, $height));
        if ($scale < 1.0) {
            $scaled = imagescale($source, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
            if ($scaled !== false) {
                $source = $scaled;
                $width = imagesx($source);
                $height = imagesy($source);
            }
        }

        $isJpeg = $mime === 'image/jpeg';
        if (! $isJpeg) {
            imagealphablending($source, false);
            imagesavealpha($source, true);
        }
        ob_start();
        $isJpeg ? imagejpeg($source, null, 88) : imagepng($source, null, 6);
        $bytes = (string) ob_get_clean();

        return ['bytes' => $bytes, 'extension' => $isJpeg ? 'jpg' : 'png', 'mime' => $isJpeg ? 'image/jpeg' : 'image/png', 'width' => $width, 'height' => $height];
    }

    /** The SVG's width-to-height ratio from its viewBox or size, or null when it cannot be read. */
    private function svgRatio(string $svg): ?float
    {
        if (preg_match('/viewBox\s*=\s*"[^"]*?([\d.]+)[\s,]+([\d.]+)"/', $svg, $box) === 1 && (float) $box[2] > 0) {
            return (float) $box[1] / (float) $box[2];
        }
        if (preg_match('/<svg[^>]*\swidth\s*=\s*"([\d.]+)(?:px)?"[^>]*\sheight\s*=\s*"([\d.]+)(?:px)?"/', $svg, $size) === 1 && (float) $size[2] > 0) {
            return (float) $size[1] / (float) $size[2];
        }

        return null;
    }

    private function detectedMime(UploadedFile $file): string
    {
        $path = $file->getRealPath();
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo !== false && $path !== false ? finfo_file($finfo, $path) : false;

        return is_string($mime) ? $mime : '';
    }
}
