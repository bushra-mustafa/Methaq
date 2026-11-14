<?php

declare(strict_types=1);

namespace App\Http\Requests\Support;

use App\Domains\Users\Enums\SupportTopic;
use App\Domains\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class OpenSupportThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'topic' => ['required', Rule::enum(SupportTopic::class)],
            'subject' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:3000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'topic.required' => 'اختاري نوع الرسالة.',
            'topic.enum' => 'اختاري نوع الرسالة من القائمة.',
            'subject.required' => 'اكتبي عنواناً قصيراً للرسالة.',
            'subject.max' => 'العنوان يجب ألا يتجاوز 120 حرفاً.',
            'body.required' => 'اكتبي رسالتك.',
            'body.max' => 'الرسالة يجب ألا تتجاوز 3000 حرف.',
        ];
    }
}
