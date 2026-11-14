<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\Enums\AssetType;
use App\Domains\Editor\Models\TemplateAsset;
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
use Throwable;

/**
 * An administrator adds a music track to the library. The file type is read from its content, never from its name;
 * the track is stored under a random name, and the administrator must state the source and confirm the right to use it.
 */
final class UploadAudioAssetAction
{
    public const MAX_BYTES = 10 * 1024 * 1024;

    /** @var array<string, string> detected content type => stored extension */
    public const TYPES = [
        'audio/mpeg' => 'mp3',
        'audio/mp4' => 'm4a',
        'audio/x-m4a' => 'm4a',
        'audio/wav' => 'wav',
        'audio/x-wav' => 'wav',
        'audio/ogg' => 'ogg',
        'application/ogg' => 'ogg',
    ];

    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(User $admin, string $name, UploadedFile $file, string $source, bool $rightsConfirmed): TemplateAsset
    {
        Gate::forUser($admin)->authorize('create', TemplateAsset::class);

        $name = trim($name);
        $source = trim($source);
        if ($name === '' || mb_strlen($name) > 120) {
            throw ValidationException::withMessages(['name' => 'اكتبي اسماً للصوتية لا يتجاوز 120 حرفاً.']);
        }
        if ($source === '' || mb_strlen($source) > 300) {
            throw ValidationException::withMessages(['source' => 'اذكري مصدر الصوتية أو ترخيصها (حتى 300 حرف).']);
        }
        if (! $rightsConfirmed) {
            throw ValidationException::withMessages(['rights' => 'أكّدي أن لديك حق استخدام هذه الصوتية داخل دعوات العملاء.']);
        }
        if (! $file->isValid() || $file->getSize() === false || $file->getSize() < 1 || $file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages(['file' => 'الملف تالف أو يتجاوز 10 ميغابايت.']);
        }
        $mime = $this->detectedMime($file);
        $extension = self::TYPES[$mime] ?? null;
        if ($extension === null) {
            throw ValidationException::withMessages(['file' => 'الملف يجب أن يكون صوتاً بصيغة MP3 أو M4A أو WAV أو OGG.']);
        }

        $stored = Str::uuid()->toString().'.'.$extension;
        $originalPath = 'editor/audio/'.$stored;
        $publicPath = 'editor/audio/'.$stored;
        $bytes = (int) $file->getSize();

        try {
            if (Storage::disk('local')->putFileAs('editor/audio', $file, $stored) === false
                || Storage::disk('public')->putFileAs('editor/audio', $file, $stored) === false) {
                throw ValidationException::withMessages(['file' => 'تعذّر حفظ الملف. حاولي مرة أخرى.']);
            }

            return DB::transaction(function () use ($admin, $name, $source, $originalPath, $publicPath, $mime, $bytes): TemplateAsset {
                $asset = new TemplateAsset;
                $asset->forceFill([
                    'slug' => 'audio-'.Str::lower(Str::random(10)),
                    'name' => $name,
                    'type' => AssetType::Audio,
                    'original_path' => $originalPath,
                    'preview_path' => 'storage/'.$publicPath,
                    'mime_type' => $mime,
                    'capabilities' => [],
                    'metadata' => ['sizeBytes' => $bytes],
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
                    metadata: ['name' => $name, 'mime' => $mime, 'sizeBytes' => $bytes],
                );

                return $asset;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($originalPath);
            Storage::disk('public')->delete($publicPath);

            throw $exception;
        }
    }

    /** The content type read from the file's own bytes, ignoring its name and whatever the browser claimed. */
    private function detectedMime(UploadedFile $file): string
    {
        $path = $file->getRealPath();
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo !== false && $path !== false ? finfo_file($finfo, $path) : false;

        return is_string($mime) ? $mime : '';
    }
}
