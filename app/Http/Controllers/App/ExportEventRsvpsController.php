<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Events\Models\Event;
use App\Domains\RSVP\Enums\AttendingStatus;
use App\Domains\RSVP\Models\Rsvp;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Support\CsvCell;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Every reply to one event as a CSV (UTF-8 with a byte-order mark, formula-safe cells). Owner only. */
final class ExportEventRsvpsController extends Controller
{
    public function __invoke(Request $request, Event $event): StreamedResponse
    {
        $owner = $request->user();
        abort_unless($owner instanceof User, 401);
        Gate::forUser($owner)->authorize('update', $event);

        $labels = [
            AttendingStatus::Attending->value => 'سيحضر',
            AttendingStatus::Declined->value => 'اعتذر',
            AttendingStatus::Maybe->value => 'ربما',
        ];
        $timezone = $event->timezone;

        return response()->streamDownload(function () use ($event, $labels, $timezone): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['الاسم', 'الهاتف', 'الحالة', 'المرافقون', 'ملاحظات', 'وقت الرد']);
            Rsvp::query()->where('event_id', $event->getKey())->orderBy('id')->lazyById(200)->each(function (Rsvp $rsvp) use ($out, $labels, $timezone): void {
                fputcsv($out, array_map(CsvCell::safe(...), [
                    $rsvp->guest_name,
                    (string) $rsvp->guest_phone,
                    $labels[$rsvp->attending_status->value],
                    (string) $rsvp->companions_count,
                    (string) $rsvp->notes,
                    $rsvp->updated_at?->timezone($timezone)->format('Y-m-d H:i') ?? '',
                ]));
            });
            fclose($out);
        }, 'methaq-rsvps-'.$event->getKey().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
