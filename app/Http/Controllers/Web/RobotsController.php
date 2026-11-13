<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * robots.txt, built per request so no production address is written into the repository.
 * The main site is open except for account, admin and prototype areas. Invitation addresses
 * belong to guests, so on any other host everything is closed to crawlers.
 */
final class RobotsController extends Controller
{
    private const PRIVATE_PATHS = ['/app', '/admin', '/dev', '/login', '/register', '/forgot-password', '/reset-password', '/two-factor-challenge', '/user', '/email'];

    public function __invoke(Request $request): Response
    {
        $mainHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $isMainSite = $request->getHost() === $mainHost || in_array($request->getHost(), ['localhost', '127.0.0.1'], true);

        $lines = ['User-agent: *'];
        if ($isMainSite) {
            foreach (self::PRIVATE_PATHS as $path) {
                $lines[] = 'Disallow: '.$path;
            }
            $lines[] = '';
            $lines[] = 'Sitemap: '.url('/sitemap.xml');
        } else {
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
