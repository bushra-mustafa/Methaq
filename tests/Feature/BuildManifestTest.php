<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * macOS ignores letter case, so a build asset can be tracked in Git as `Presentation-x.css` while the manifest asks for
 * `presentation-x.css`. Linux then returns 404 for it. A tracked build file whose name differs from the manifest only by letter case is a deploy bug.
 */
final class BuildManifestTest extends TestCase
{
    public function test_every_file_in_the_build_manifest_is_tracked_in_git_with_the_same_letter_case(): void
    {
        $root = dirname(__DIR__, 2);
        $manifestPath = $root.'/public/build/manifest.json';
        if (! is_file($manifestPath) || ! is_dir($root.'/.git')) {
            $this->markTestSkipped('The build manifest or the Git checkout is unavailable.');
        }

        $tracked = array_flip(explode("\n", trim((string) shell_exec('cd '.escapeshellarg($root).' && git ls-files public/build 2>/dev/null'))));
        if ($tracked === [] || isset($tracked[''])) {
            $this->markTestSkipped('Build files are not tracked in this checkout.');
        }

        $lowercased = array_change_key_case($tracked, CASE_LOWER);
        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        $missing = [];
        foreach ($manifest as $entry) {
            foreach ([$entry['file'] ?? null, ...($entry['css'] ?? []), ...($entry['assets'] ?? [])] as $file) {
                $path = 'public/build/'.$file;
                // A file that is merely not committed yet is fine; a different spelling of the same name is the bug.
                if (is_string($file) && ! isset($tracked[$path]) && isset($lowercased[strtolower($path)])) {
                    $missing[] = $file;
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)), 'Manifest files not tracked with the same letter case.');
    }
}
