<?php

declare(strict_types=1);

namespace App\Http\Requests\RSVP;

use App\Domains\Editor\Enums\TemplateFeature;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Services\EventEntitlements;
use App\Domains\RSVP\DTOs\CreateRsvpData;
use App\Domains\RSVP\Enums\AttendingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreRsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->attributes->get('publicEvent');

        return $event instanceof Event && app(EventEntitlements::class)->allows($event, TemplateFeature::Rsvp);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'submission_token' => ['required', 'uuid'],
            'guest_name' => ['required', 'string', 'max:120'],
            'guest_phone' => ['nullable', 'string', 'max:32'],
            'attending_status' => ['required', Rule::enum(AttendingStatus::class)],
            'companions_count' => ['required', 'integer', 'min:0', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toData(): CreateRsvpData
    {
        /** @var array{submission_token: string, guest_name: string, guest_phone?: string|null, attending_status: string, companions_count: int|string, notes?: string|null} $validated */
        $validated = $this->validated();

        return new CreateRsvpData(
            submissionToken: $validated['submission_token'],
            guestName: $validated['guest_name'],
            guestPhone: $validated['guest_phone'] ?? null,
            attendingStatus: AttendingStatus::from($validated['attending_status']),
            companionsCount: (int) $validated['companions_count'],
            notes: $validated['notes'] ?? null,
        );
    }
}
