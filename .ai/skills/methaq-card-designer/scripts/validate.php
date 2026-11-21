<?php

declare(strict_types=1);

/*
 * Validates one compiled design against the real Methaq classes (read-only use of the project's autoloader).
 * build.mjs runs it; by hand:  php validate.php <designDir> <methaqProjectDir>
 * Prints a JSON report: {"errors": [...], "warnings": [...], "info": {...}}.
 */

use App\Domains\Editor\Services\DesignDocumentHydrator;
use App\Domains\Editor\Services\SvgAssetValidator;
use App\Domains\Editor\Services\TemplateScripture;
use App\Domains\Events\Enums\EventCategory;

[$script, $designDir, $project] = $argv + [null, null, null];
if ($designDir === null || $project === null) {
    fwrite(STDERR, "usage: php validate.php <designDir> <methaqProjectDir>\n");
    exit(2);
}

require rtrim($project, '/').'/vendor/autoload.php';

$errors = [];
$warnings = [];
$info = [];

$designPath = rtrim($designDir, '/').'/design.json';
if (! is_file($designPath)) {
    echo json_encode(['errors' => ['design.json is missing'], 'warnings' => [], 'info' => []]);
    exit(0);
}

$raw = file_get_contents($designPath);
$design = json_decode((string) $raw, true);
if (! is_array($design)) {
    echo json_encode(['errors' => ['design.json is not valid JSON'], 'warnings' => [], 'info' => []]);
    exit(0);
}

if (EventCategory::tryFrom((string) ($design['category'] ?? '')) === null) {
    $errors[] = 'category is not a Methaq EventCategory';
}

// 1. The document must hydrate exactly as the application would hydrate it.
try {
    $document = (new DesignDocumentHydrator)->hydrate($design['document'] ?? []);
    $info['layers'] = count($document->canvas->layers);
    $info['jsonBytes'] = strlen((string) json_encode($design['document']));
    if ($info['jsonBytes'] > 1_048_576) {
        $errors[] = 'document JSON exceeds 1 MiB';
    }
} catch (Throwable $exception) {
    $errors[] = 'document: '.$exception->getMessage();
}

// 2. Every SVG must pass the library's safety validator.
$validator = new SvgAssetValidator;
foreach ($design['assets'] ?? [] as $asset) {
    $svgPath = rtrim($designDir, '/').'/'.($asset['svg'] ?? '');
    if (! is_file($svgPath)) {
        $errors[] = "asset {$asset['key']}: SVG file missing";

        continue;
    }
    $contents = (string) file_get_contents($svgPath);
    try {
        $validator->assertSafe($contents);
    } catch (Throwable $exception) {
        $errors[] = "asset {$asset['key']}: ".$exception->getMessage();

        continue;
    }
    $info['svgBytes'][$asset['key']] = strlen($contents);
    if (preg_match('/<text[\s>]/i', $contents) === 1) {
        $warnings[] = "asset {$asset['key']}: contains <text>; fonts do not load inside SVG images, convert lettering to paths";
    }
    if (preg_match('/viewBox="0 0 (\d+(?:\.\d+)?) (\d+(?:\.\d+)?)"/', $contents, $match) !== 1) {
        $errors[] = "asset {$asset['key']}: needs viewBox=\"0 0 W H\"";
    } elseif ((float) $match[1] !== (float) $asset['width'] || (float) $match[2] !== (float) $asset['height']) {
        $errors[] = "asset {$asset['key']}: viewBox {$match[1]}x{$match[2]} differs from declared {$asset['width']}x{$asset['height']}";
    }
    $pngPath = rtrim($designDir, '/').'/'.($asset['preview'] ?? '');
    $size = is_file($pngPath) ? getimagesize($pngPath) : false;
    if ($size === false) {
        $errors[] = "asset {$asset['key']}: PNG preview missing";
    } elseif ($size[0] !== (int) $asset['width'] || $size[1] !== (int) $asset['height']) {
        $errors[] = "asset {$asset['key']}: PNG preview is {$size[0]}x{$size[1]}, expected {$asset['width']}x{$asset['height']}";
    }
}

// 3. Quranic text must be the Tanzil text, verbatim.
$verseContents = array_map(static fn (string $id): string => TemplateScripture::content($id), array_keys(TemplateScripture::VERSES));
foreach ($design['document']['canvas']['layers'] ?? [] as $layer) {
    if (($layer['type'] ?? '') === 'text' && str_contains((string) $layer['content'], '﴿')) {
        if (! in_array($layer['content'], $verseContents, true)) {
            $errors[] = "layer {$layer['id']}: Quran text differs from TemplateScripture";
        }
    }
}

echo json_encode(['errors' => $errors, 'warnings' => $warnings, 'info' => $info], JSON_UNESCAPED_UNICODE);
