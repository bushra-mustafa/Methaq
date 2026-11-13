<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Request;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * A reusable guard against dead links: every literal internal link written in the React source
 * must resolve to a Laravel route, and every literal image or asset path must exist in /public.
 * Dynamic links (template strings) are covered by the page tests that render them.
 */
final class InternalLinksTest extends TestCase
{
    public function test_every_literal_internal_link_in_the_frontend_resolves_to_a_route(): void
    {
        $dead = [];
        $checked = 0;

        foreach ($this->literals('/(?:href|to)[=:]\s*[{]?\s*["\'](\/[^"\'#?{$\s]*)/') as $file => $paths) {
            foreach (array_unique($paths) as $path) {
                if ($path === '/dev/design-lab') {
                    continue; // local-only prototype
                }

                $checked++;

                try {
                    app('router')->getRoutes()->match(Request::create($path, 'GET'));
                } catch (MethodNotAllowedHttpException) {
                    // The route exists but answers another verb (for example the POST-only logout link).
                } catch (NotFoundHttpException) {
                    $dead[] = "{$file}: {$path}";
                }
            }
        }

        $this->assertGreaterThan(15, $checked, 'The link scan found suspiciously few links.');
        $this->assertSame([], $dead, "Dead internal links:\n".implode("\n", $dead));
    }

    public function test_every_literal_image_and_asset_path_exists_in_public(): void
    {
        $missing = [];

        foreach ($this->literals('/(?:src|href)[=:]\s*[{]?\s*["\'](\/(?:brand|editor|fonts|audio|images)[^"\'#?{$\s]*)/') as $file => $paths) {
            foreach (array_unique($paths) as $path) {
                if (! is_file(public_path(ltrim($path, '/')))) {
                    $missing[] = "{$file}: {$path}";
                }
            }
        }

        $this->assertSame([], $missing, "Missing public assets:\n".implode("\n", $missing));
    }

    /** @return array<string, list<string>> */
    private function literals(string $pattern): array
    {
        $found = [];

        foreach ((new Finder)->files()->in(resource_path('js'))->name(['*.tsx', '*.ts']) as $file) {
            if (preg_match_all($pattern, $file->getContents(), $matches) > 0) {
                $found[$file->getRelativePathname()] = $matches[1];
            }
        }

        return $found;
    }
}
