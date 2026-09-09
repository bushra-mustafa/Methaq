<?php

declare(strict_types=1);

namespace App\Http\Requests\Editor;

use App\Domains\Editor\Enums\LibraryResource;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateLibraryItemStatusRequest extends FormRequest
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
            'is_active' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function resource(): LibraryResource
    {
        return LibraryResource::from((string) $this->route('resource'));
    }

    public function itemId(): int
    {
        return (int) $this->route('id');
    }

    public function isActive(): bool
    {
        return $this->boolean('is_active');
    }

    public function reason(): string
    {
        return trim((string) $this->validated('reason'));
    }
}
