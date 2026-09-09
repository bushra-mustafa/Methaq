<?php

declare(strict_types=1);

namespace App\Http\Requests\Editor;

use App\Domains\Events\Enums\EventCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BrowseTemplateLibraryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', Rule::enum(EventCategory::class)],
        ];
    }

    public function category(): ?EventCategory
    {
        $category = $this->validated('category');

        return is_string($category) ? EventCategory::from($category) : null;
    }
}
