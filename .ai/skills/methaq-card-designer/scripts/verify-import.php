<?php

declare(strict_types=1);

/*
 * Proves an imported template is the design that was rendered and approved:
 *  1. TemplateDesignFactory builds the preset with stand-in assets that carry design.json's placeholder ids, and the
 *     result must equal design.json's document field for field;
 *  2. the seeder defines the template and every asset, with the right sizes, types, files and font list;
 *  3. the copied SVG and PNG files are identical to the design folder's and the SVGs pass SvgAssetValidator.
 * Reads only; the database is not touched.
 *   php verify-import.php <designDir> <methaqProjectDir>
 */

use App\Domains\Editor\Enums\TemplatePreset;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Editor\Services\SvgAssetValidator;
use App\Domains\Editor\Services\TemplateDesignFactory;
use Database\Seeders\TemplateLibrarySeeder;
use Illuminate\Contracts\Console\Kernel;

[, $designDir, $project] = $argv + [null, null, null];
if ($designDir === null || $project === null) {
    fwrite(STDERR, "usage: php verify-import.php <designDir> <methaqProjectDir>\n");
    exit(2);
}
$designDir = rtrim($designDir, '/');
$project = rtrim((string) realpath($project), '/');
require $project.'/vendor/autoload.php';
$app = require $project.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/** @var array<string, mixed> $design */
$design = json_decode((string) file_get_contents($designDir.'/design.json'), true, flags: JSON_THROW_ON_ERROR);
/** @var array<string, mixed> $projectData */
$projectData = json_decode((string) shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/project-data.php').' '.escapeshellarg($project)), true, flags: JSON_THROW_ON_ERROR);
$errors = [];

/** Field-by-field comparison; numbers compare by value (1 equals 1.0), a missing key equals null. */
function compareValues(mixed $actual, mixed $expected, string $at, array &$errors): void
{
    if ((is_int($actual) || is_float($actual)) && (is_int($expected) || is_float($expected))) {
        if (abs((float) $actual - (float) $expected) > 1e-9) {
            $errors[] = "{$at}: factory gives {$actual}, design.json has {$expected}";
        }

        return;
    }
    if (is_array($actual) && is_array($expected)) {
        if (array_is_list($actual) && array_is_list($expected)) {
            if (count($actual) !== count($expected)) {
                $errors[] = "{$at}: factory gives ".count($actual).' items, design.json has '.count($expected);
            }
            foreach ($expected as $index => $value) {
                compareValues($actual[$index] ?? null, $value, "{$at}[{$index}]", $errors);
            }

            return;
        }
        foreach (array_unique([...array_keys($actual), ...array_keys($expected)]) as $key) {
            $label = is_array($expected) && array_key_exists('id', $expected) ? "{$at}({$expected['id']}).{$key}" : "{$at}.{$key}";
            compareValues($actual[$key] ?? null, $expected[$key] ?? null, $label, $errors);
        }

        return;
    }
    if ($actual !== $expected) {
        $errors[] = "{$at}: factory gives ".json_encode($actual, JSON_UNESCAPED_UNICODE).', design.json has '.json_encode($expected, JSON_UNESCAPED_UNICODE);
    }
}

// 1. The factory output equals the approved document.
$preset = TemplatePreset::tryFrom((string) $design['slug']);
if ($preset === null) {
    $errors[] = "TemplatePreset has no case '{$design['slug']}'";
} else {
    $standIn = static function (string $id): TemplateAsset {
        $asset = new TemplateAsset;
        $asset->forceFill(['id' => (int) $id, 'asset_version' => 1]);

        return $asset;
    };
    $assets = [];
    foreach ($design['assets'] as $asset) {
        $assets[$asset['slug']] = $standIn($asset['placeholderId']);
    }
    foreach ($projectData['fonts'] as $slug => $font) {
        $assets[$slug] = $standIn($font['placeholderId']);
    }
    foreach ($projectData['templateAudio'] as $slug => $track) {
        $assets[$slug] = $standIn($track['placeholderId']);
    }
    try {
        $built = $app->make(TemplateDesignFactory::class)->make($preset, $assets)->toArray();
        compareValues($built, $design['document'], 'document', $errors);
    } catch (Throwable $exception) {
        $errors[] = 'factory: '.$exception->getMessage();
    }
}

// 2. Seeder definitions.
$seeder = $app->make(TemplateLibrarySeeder::class);
$definitions = static fn (string $method): array => (new ReflectionMethod($seeder, $method))->invoke($seeder);
$templateDefinition = collect($definitions('templateDefinitions'))->firstWhere('slug', $design['slug']);
if ($templateDefinition === null) {
    $errors[] = "templateDefinitions() has no entry for '{$design['slug']}'";
} else {
    if ($templateDefinition['preset'] !== $preset) {
        $errors[] = 'template definition: preset does not match the slug';
    }
    if ($templateDefinition['category']->value !== $design['category']) {
        $errors[] = "template definition: category is {$templateDefinition['category']->value}, design is {$design['category']}";
    }
    if ($templateDefinition['name'] !== $design['name']) {
        $errors[] = "template definition: name «{$templateDefinition['name']}» differs from «{$design['name']}»";
    }
    $expectedLinks = [...array_column($design['assets'], 'slug'), ...$design['fonts']];
    $missingLinks = array_diff($expectedLinks, $templateDefinition['assets']);
    if ($missingLinks !== []) {
        $errors[] = 'template definition: assets list lacks '.implode(', ', $missingLinks);
    }
    if (! is_file(public_path($templateDefinition['thumbnail_path']))) {
        $errors[] = "thumbnail {$templateDefinition['thumbnail_path']} is missing";
    } elseif (sha1_file(public_path($templateDefinition['thumbnail_path'])) !== sha1_file($designDir.'/thumb.png')) {
        $errors[] = "thumbnail {$templateDefinition['thumbnail_path']} differs from the design's thumb.png (rebuilt after import?)";
    }
    foreach (['webp', 'avif'] as $extension) {
        if (! is_file(public_path(substr($templateDefinition['thumbnail_path'], 0, -4).'.'.$extension))) {
            $errors[] = "{$extension} twin of the thumbnail is missing";
        }
    }
}

// 3. Asset definitions and files.
$assetDefinitions = collect($definitions('assetDefinitions'))->keyBy('slug');
$validator = new SvgAssetValidator;
$privateRoot = rtrim((string) config('filesystems.disks.local.root'), '/');
foreach ($design['assets'] as $asset) {
    $definition = $assetDefinitions->get($asset['slug']);
    if ($definition === null) {
        $errors[] = "assetDefinitions() has no entry for '{$asset['slug']}'";

        continue;
    }
    if ($definition['type']->value !== $asset['type'] || $definition['width'] !== $asset['width'] || $definition['height'] !== $asset['height']) {
        $errors[] = "asset {$asset['slug']}: definition is {$definition['type']->value} {$definition['width']}x{$definition['height']}, design is {$asset['type']} {$asset['width']}x{$asset['height']}";
    }
    $original = $privateRoot.'/'.$definition['original_path'];
    $preview = public_path($definition['preview_path']);
    if (! is_file($original) || sha1_file($original) !== sha1_file($designDir.'/'.$asset['svg'])) {
        $errors[] = "asset {$asset['slug']}: {$definition['original_path']} is missing or differs from {$asset['svg']}";
    } else {
        try {
            $validator->assertSafe((string) file_get_contents($original));
        } catch (Throwable $exception) {
            $errors[] = "asset {$asset['slug']}: ".$exception->getMessage();
        }
    }
    if (! is_file($preview) || sha1_file($preview) !== sha1_file($designDir.'/'.$asset['preview'])) {
        $errors[] = "asset {$asset['slug']}: {$definition['preview_path']} is missing or differs from {$asset['preview']}";
    }
}

if ($errors !== []) {
    echo "FAIL  {$design['slug']}\n";
    foreach ($errors as $error) {
        echo "  ERROR   {$error}\n";
    }
    exit(1);
}
echo "OK  {$design['slug']}: the factory rebuilds design.json exactly; seeder entries and files are in place\n";
