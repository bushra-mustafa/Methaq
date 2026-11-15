<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Domains\Events\Models\Event;
use App\Domains\Users\Services\CloudMsgSettings;
use Illuminate\Foundation\Http\FormRequest;

final class PrepareInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return CloudMsgSettings::get('enabled') && $event instanceof Event && $this->user()?->can('update', $event);
    }

    public function rules(): array
    {
        return [
            'render' => ['required', 'file', 'mimetypes:image/png', 'max:'.PublishEventRequest::MAX_RENDER_KILOBYTES],
            'expectedRevision' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ];
    }
}
