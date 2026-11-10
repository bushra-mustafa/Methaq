<?php

declare(strict_types=1);

use App\Http\Controllers\Guest\ShowInvitationController;
use App\Http\Controllers\Guest\StoreRsvpController;
use Illuminate\Support\Facades\Route;

Route::get('/', ShowInvitationController::class)
    ->name('invitation.show');

Route::post('/rsvp', StoreRsvpController::class)
    ->name('invitation.rsvp.store');
