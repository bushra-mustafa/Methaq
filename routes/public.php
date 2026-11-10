<?php

declare(strict_types=1);

use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\TemplateLibraryController;
use App\Http\Controllers\Web\AboutController;
use App\Http\Controllers\Web\ContactController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)
    ->name('home');

Route::get('/templates', TemplateLibraryController::class)
    ->name('templates.index');

Route::get('/about', AboutController::class)
    ->name('about');

Route::get('/contact', ContactController::class)
    ->name('contact');