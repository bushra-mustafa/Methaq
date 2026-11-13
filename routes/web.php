<?php

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
    });
Route::get('/dev/design-lab', DesignLabController::class)
    ->middleware(LocalDevelopmentOnly::class)
    ->name('dev.design-lab');
