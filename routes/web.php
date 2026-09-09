<?php

declare(strict_types=1);

use App\Http\Controllers\App\Admin\AdminDashboardController;
use App\Http\Controllers\App\Admin\LibraryController;
use App\Http\Controllers\App\Admin\UpdateLibraryItemStatusController;
use App\Http\Controllers\App\CreateEventController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\DesignLabController;
use App\Http\Controllers\App\LogoutOtherSessionsController;
use App\Http\Controllers\App\ProfileController;
use App\Http\Controllers\App\ShowEventController;
use App\Http\Controllers\App\StoreEventController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\TemplateLibraryController;
use App\Http\Middleware\LocalDevelopmentOnly;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/templates', TemplateLibraryController::class)->name('templates.index');

Route::middleware(['auth', 'active'])->prefix('app')->name('app.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/events/create', CreateEventController::class)->name('events.create');
    Route::post('/events', StoreEventController::class)->name('events.store');
    Route::get('/events/{event}', ShowEventController::class)->whereNumber('event')->name('events.show');
    Route::get('/profile', ProfileController::class)->name('profile');
    Route::delete('/sessions/others', LogoutOtherSessionsController::class)->name('sessions.destroy-others');

    Route::middleware('admin.2fa')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('index');
        Route::get('/library', LibraryController::class)->name('library.index');
        Route::patch('/library/{resource}/{id}/status', UpdateLibraryItemStatusController::class)
            ->whereIn('resource', ['template', 'asset', 'collection'])
            ->whereNumber('id')
            ->name('library.status.update');
    });
});

Route::get('/dev/design-lab', DesignLabController::class)
    ->middleware(LocalDevelopmentOnly::class)
    ->name('dev.design-lab');
