<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Domains\Events\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateEventPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Event::class) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'template' => [
                'nullable',
                'string',
                'max:80',
                Rule::exists('templates', 'slug')->where('is_active', true),
            ],
        ];
    }

    public function templateSlug(): ?string
    {
        $slug = $this->validated('template');

        return is_string($slug) ? $slug : null;
    }
}
