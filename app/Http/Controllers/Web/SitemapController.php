<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domains\Events\Enums\EventCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/** The public pages a search engine may index; invitations and account pages are never listed. */
final class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $paths = ['/', '/templates', '/about', '/contact', '/privacy'];
        foreach (EventCategory::cases() as $category) {
            $paths[] = route('occasions.show', $category->slug(), false);
        }

        $urls = implode('', array_map(static fn (string $path): string => '<url><loc>'.e(url($path)).'</loc></url>', $paths));

        return response('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$urls.'</urlset>', 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
