<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\LibraryController;
use App\Http\Controllers\Admin\UpdateLibraryItemStatusController;
use Illuminate\Support\Facades\Route;

Route::get('/', AdminDashboardController::class)
    ->name('index');

Route::get('/library', LibraryController::class)
    ->name('library.index');

Route::patch(
    '/library/{resource}/{id}/status',
    UpdateLibraryItemStatusController::class
)
    ->whereIn('resource', ['template', 'asset', 'collection'])
    ->whereNumber('id')
    ->name('library.status.update');
