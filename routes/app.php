<?php

declare(strict_types=1);

use App\Http\Controllers\App\CreateEventController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\EventEditorController;
use App\Http\Controllers\App\EventPaymentController;
use App\Http\Controllers\App\EventPresentationController;
use App\Http\Controllers\App\IvoryProposalController;
use App\Http\Controllers\App\LogoutOtherSessionsController;
use App\Http\Controllers\App\ProfileController;
use App\Http\Controllers\App\SaveEventDesignController;
use App\Http\Controllers\App\SaveProposalDraftController;
use App\Http\Controllers\App\ShowEventController;
use App\Http\Controllers\App\StoreEventController;
use App\Http\Controllers\App\SubmitPaymentReceiptController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)
    ->name('dashboard');

Route::get('/proposals/ivory-wedding', IvoryProposalController::class)
    ->name('proposals.ivory');

Route::patch('/proposals/ivory-wedding', SaveProposalDraftController::class)
    ->name('proposals.ivory.update');

Route::get('/events/create', CreateEventController::class)
    ->name('events.create');

Route::post('/events', StoreEventController::class)
    ->name('events.store');

Route::get('/events/{event}', ShowEventController::class)
    ->whereNumber('event')
    ->name('events.show');

Route::get('/events/{event}/editor', EventEditorController::class)
    ->whereNumber('event')
    ->name('events.editor');

Route::get('/events/{event}/presentation', EventPresentationController::class)
    ->whereNumber('event')
    ->name('events.presentation');

Route::get('/events/{event}/payment', EventPaymentController::class)
    ->whereNumber('event')
    ->name('events.payment');

Route::post('/events/{event}/payment', SubmitPaymentReceiptController::class)
    ->whereNumber('event')
    ->middleware('throttle:6,1')
    ->name('events.payment.store');

Route::patch('/events/{event}/design', SaveEventDesignController::class)
    ->whereNumber('event')
    ->name('events.design.update');

Route::get('/profile', ProfileController::class)
    ->name('profile');

Route::delete('/sessions/others', LogoutOtherSessionsController::class)
    ->name('sessions.destroy-others');
