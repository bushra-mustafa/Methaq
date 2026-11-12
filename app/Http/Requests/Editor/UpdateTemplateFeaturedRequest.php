<?php

declare(strict_types=1);

namespace App\Http\Requests\Editor;

use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateTemplateFeaturedRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->isActive() && $user->role === UserRole::Admin;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'is_featured' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function templateId(): int
    {
        return (int) $this->route('id');
    }

    public function isFeatured(): bool
    {
        return $this->boolean('is_featured');
    }

    public function reason(): string
    {
        return trim((string) $this->validated('reason'));
    }
}
