<?php

declare(strict_types=1);

use App\Http\Controllers\App\DesignLabController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Middleware\LocalDevelopmentOnly;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/dev/design-lab', DesignLabController::class)
    ->middleware(LocalDevelopmentOnly::class)
    ->name('dev.design-lab');
