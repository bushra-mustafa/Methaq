<?php

declare(strict_types=1);

use App\Http\Controllers\App\Admin\AdminDashboardController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\DesignLabController;
use App\Http\Controllers\App\LogoutOtherSessionsController;
use App\Http\Controllers\App\ProfileController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Middleware\LocalDevelopmentOnly;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth', 'active'])->prefix('app')->name('app.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/profile', ProfileController::class)->name('profile');
    Route::delete('/sessions/others', LogoutOtherSessionsController::class)->name('sessions.destroy-others');

    Route::middleware('admin.2fa')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('index');
    });
});

Route::get('/dev/design-lab', DesignLabController::class)
    ->middleware(LocalDevelopmentOnly::class)
    ->name('dev.design-lab');
