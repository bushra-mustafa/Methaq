<?php

// declare(strict_types=1);

// use App\Http\Controllers\Admin\AdminDashboardController;
// use App\Http\Controllers\Admin\LibraryController;
// use App\Http\Controllers\Admin\UpdateLibraryItemStatusController;
// use App\Http\Controllers\App\CreateEventController;
// use App\Http\Controllers\App\DashboardController;
// use App\Http\Controllers\App\DesignLabController;
// use App\Http\Controllers\App\EventEditorController;
// use App\Http\Controllers\App\EventPresentationController;
// use App\Http\Controllers\App\IvoryProposalController;
// use App\Http\Controllers\App\LogoutOtherSessionsController;
// use App\Http\Controllers\App\ProfileController;
// use App\Http\Controllers\App\SaveEventDesignController;
// use App\Http\Controllers\App\SaveProposalDraftController;
// use App\Http\Controllers\App\ShowEventController;
// use App\Http\Controllers\App\StoreEventController;
// use App\Http\Controllers\Guest\ShowInvitationController;
// use App\Http\Controllers\Guest\StoreRsvpController;
// use App\Http\Controllers\Web\HomeController;
// use App\Http\Controllers\Web\TemplateLibraryController;
// use App\Http\Middleware\LocalDevelopmentOnly;
// use App\Http\Middleware\ResolvePublishedEvent;
// use Illuminate\Support\Facades\Route;

// Route::domain('{subdomain}.'.config('methaq.invitation_domain'))
//     ->middleware(ResolvePublishedEvent::class)
//     ->group(function (): void {
//         Route::get('/', ShowInvitationController::class)->name('invitation.show');
//         Route::post('/rsvp', StoreRsvpController::class)->name('invitation.rsvp.store');
//     });

// Route::get('/', HomeController::class)->name('home');
// Route::get('/templates', TemplateLibraryController::class)->name('templates.index');

// Route::middleware(['auth', 'active'])->prefix('app')->name('app.')->group(function (): void {
//     Route::get('/', DashboardController::class)->name('dashboard');
//     Route::get('/proposals/ivory-wedding', IvoryProposalController::class)->name('proposals.ivory');
//     Route::patch('/proposals/ivory-wedding', SaveProposalDraftController::class)->name('proposals.ivory.update');
//     Route::get('/events/create', CreateEventController::class)->name('events.create');
//     Route::post('/events', StoreEventController::class)->name('events.store');
//     Route::get('/events/{event}', ShowEventController::class)->whereNumber('event')->name('events.show');
//     Route::get('/events/{event}/editor', EventEditorController::class)->whereNumber('event')->name('events.editor');
//     Route::get('/events/{event}/presentation', EventPresentationController::class)->whereNumber('event')->name('events.presentation');
//     Route::patch('/events/{event}/design', SaveEventDesignController::class)->whereNumber('event')->name('events.design.update');
//     Route::get('/profile', ProfileController::class)->name('profile');
//     Route::delete('/sessions/others', LogoutOtherSessionsController::class)->name('sessions.destroy-others');

//     Route::middleware('admin.2fa')->prefix('admin')->name('admin.')->group(function (): void {
//         Route::get('/', AdminDashboardController::class)->name('index');
//         Route::get('/library', LibraryController::class)->name('library.index');
//         Route::patch('/library/{resource}/{id}/status', UpdateLibraryItemStatusController::class)
//             ->whereIn('resource', ['template', 'asset', 'collection'])
//             ->whereNumber('id')
//             ->name('library.status.update');
//     });
// });

// Route::get('/dev/design-lab', DesignLabController::class)
//     ->middleware(LocalDevelopmentOnly::class)
//     ->name('dev.design-lab');

declare(strict_types=1);

use App\Http\Controllers\App\DesignLabController;
use App\Http\Middleware\LocalDevelopmentOnly;
use App\Http\Middleware\ResolvePublishedEvent;
use Illuminate\Support\Facades\Route;

// Guest invitations
Route::domain('{subdomain}.'.config('methaq.invitation_domain'))
    ->middleware(ResolvePublishedEvent::class)
    ->group(function (): void {
        require __DIR__.'/guest.php';
    });

// Public website
require __DIR__.'/public.php';

// Authenticated application
Route::middleware(['auth', 'active'])
    ->prefix('app')
    ->name('app.')
    ->group(function (): void {

        require __DIR__.'/app.php';

        // Administration
        Route::middleware('admin.2fa')
            ->prefix('admin')
            ->name('admin.')
            ->group(function (): void {
                require __DIR__.'/admin.php';
            });
    });
Route::get('/dev/design-lab', DesignLabController::class)
    ->middleware(LocalDevelopmentOnly::class)
    ->name('dev.design-lab');
