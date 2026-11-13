<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

final class PublishEventRequest extends FormRequest
{
    public const MAX_RENDER_KILOBYTES = 6144;

    public function authorize(): bool
    {
        $owner = $this->user();
        $event = $this->route('event');

        return $owner instanceof User && $event instanceof Event && $owner->can('publish', $event);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'render' => ['required', 'file', 'mimetypes:image/png', 'max:'.self::MAX_RENDER_KILOBYTES],
            'expectedRevision' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'status' => ['prohibited'],
            'is_paid' => ['prohibited'],
            'published_at' => ['prohibited'],
            'expires_at' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'render.required' => 'صورة البطاقة غير جاهزة بعد. انتظري اكتمال المعاينة.',
            'render.mimetypes' => 'صورة البطاقة غير صالحة. حدّثي الصفحة وحاولي مرة أخرى.',
            'render.max' => 'حجم صورة البطاقة أكبر من المسموح.',
            'render.uploaded' => 'تعذّر رفع صورة البطاقة. حاولي مرة أخرى.',
        ];
    }

    public function render(): UploadedFile
    {
        $render = $this->file('render');
        abort_unless($render instanceof UploadedFile, 422);

        return $render;
    }

    public function expectedRevision(): int
    {
        return (int) $this->validated('expectedRevision');
    }
}
