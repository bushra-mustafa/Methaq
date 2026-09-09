<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Domains\Events\DTOs\CreateEventData;
use App\Domains\Events\Enums\EventCategory;
use App\Domains\Events\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Throwable;

final class CreateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Event::class) === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['title' => trim((string) $this->input('title'))]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', 'string', Rule::enum(EventCategory::class)],
            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('templates', 'id')->where('is_active', true),
            ],
            'event_date' => ['required', 'string', 'date_format:Y-m-d\TH:i'],
            'timezone' => ['required', 'string', 'timezone:all'],
            'user_id' => ['prohibited'],
            'subdomain' => ['prohibited'],
            'expires_at' => ['prohibited'],
            'status' => ['prohibited'],
            'is_paid' => ['prohibited'],
            'paid_at' => ['prohibited'],
            'published_at' => ['prohibited'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('event_date') || $validator->errors()->has('timezone')) {
                return;
            }

            try {
                $eventDate = $this->localEventDate();
            } catch (Throwable) {
                $validator->errors()->add('event_date', 'موعد المناسبة غير صالح لهذه المنطقة الزمنية.');

                return;
            }

            if ($eventDate->format('Y-m-d\TH:i') !== $this->input('event_date') || $eventDate->lessThanOrEqualTo(CarbonImmutable::now($eventDate->getTimezone()))) {
                $validator->errors()->add('event_date', 'اختاري موعداً مستقبلياً صالحاً للمناسبة.');
            }
        }];
    }

    public function toData(): CreateEventData
    {
        $validated = $this->validated();

        return new CreateEventData(
            title: $validated['title'],
            category: EventCategory::from($validated['category']),
            templateId: isset($validated['template_id']) ? (int) $validated['template_id'] : null,
            eventDate: $this->localEventDate(),
            timezone: $validated['timezone'],
        );
    }

    private function localEventDate(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat(
            'Y-m-d\TH:i',
            (string) $this->input('event_date'),
            (string) $this->input('timezone'),
        );
    }
}
