<?php

declare(strict_types=1);

namespace App\Http\Requests\Support;

use App\Domains\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class ReplySupportThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:3000']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'body.required' => 'اكتبي رسالتك.',
            'body.max' => 'الرسالة يجب ألا تتجاوز 3000 حرف.',
        ];
    }
}
