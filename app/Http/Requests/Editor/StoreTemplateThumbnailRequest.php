<?php

declare(strict_types=1);

namespace App\Http\Requests\Editor;

use App\Domains\Editor\Models\Template;
use App\Domains\Users\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreTemplateThumbnailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $admin = $this->user();
        $template = $this->route('template');

        return $admin instanceof User && $template instanceof Template && $admin->can('design', $template);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return ['preview' => ['required', 'file', 'mimetypes:image/png', 'max:2048']];
    }
}
