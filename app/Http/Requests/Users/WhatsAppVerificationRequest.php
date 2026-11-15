<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use App\Domains\Users\Services\CloudMsg;
use App\Domains\Users\Services\CloudMsgSettings;
use Illuminate\Foundation\Http\FormRequest;

final class WhatsAppVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return CloudMsgSettings::get('enabled') && $this->user()?->isActive();
    }

    protected function prepareForValidation(): void
    {
        if ($this->routeIs('app.whatsapp.send-code') && is_string($this->input('phone'))) {
            $this->merge(['phone' => CloudMsg::phone($this->input('phone')) ?? $this->input('phone')]);
        }
    }

    public function rules(): array
    {
        return $this->routeIs('app.whatsapp.send-code')
            ? ['phone' => ['required', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/D']]
            : ['code' => ['required', 'string', 'regex:/^[0-9]{6}$/D']];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'اكتبي الرقم بالصيغة الدولية مثل +21891XXXXXXX.', 'code.regex' => 'اكتبي الرمز المكوّن من ستة أرقام.'];
    }
}
