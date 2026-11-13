<?php

declare(strict_types=1);

use App\Http\Controllers\Web\AboutController;
use App\Http\Controllers\Web\ContactController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\OccasionController;
use App\Http\Controllers\Web\PrivacyController;
use App\Http\Controllers\Web\RobotsController;
use App\Http\Controllers\Web\SitemapController;
use App\Http\Controllers\Web\StartFromTemplateController;
use App\Http\Controllers\Web\TemplateLibraryController;
use App\Http\Controllers\Web\TermsController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)
    ->name('home');

Route::get('/templates', TemplateLibraryController::class)
    ->name('templates.index');

Route::get('/start/{slug}', StartFromTemplateController::class)
    ->where('slug', '[a-z0-9-]+')
    ->name('templates.use');

Route::get('/occasions/{slug}', OccasionController::class)
    ->where('slug', '[a-z-]+')
    ->name('occasions.show');

Route::get('/sitemap.xml', SitemapController::class)
    ->name('sitemap');

Route::get('/robots.txt', RobotsController::class)
    ->name('robots');

Route::get('/about', AboutController::class)
    ->name('about');

Route::get('/contact', ContactController::class)
    ->name('contact');

Route::get('/privacy', PrivacyController::class)
    ->name('privacy');

Route::get('/terms', TermsController::class)
    ->name('terms');
