<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Keeps dashboard statuses in step with guest links shortly after midnight in Tripoli.
 * Needs the server cron: * * * * * php artisan schedule:run
 */
Schedule::command('methaq:expire-invitations')
    ->dailyAt('00:05')
    ->timezone((string) config('methaq.invitation_timezone', 'Africa/Tripoli'))
    ->withoutOverlapping();
