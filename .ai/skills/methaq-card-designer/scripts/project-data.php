<?php

declare(strict_types=1);

/*
 * Prints, as JSON, the live lists a card design must respect, read from the Methaq classes themselves
 * through the project's autoloader. Nothing is booted and nothing is written.
 *   php project-data.php <methaqProjectDir>
 *
 * Placeholder ids: fonts 9100001+ (seeder order), the template audio track 9200001; design assets get 9000001+ in compile.mjs.
 * verify-import.php gives the same ids to stand-in assets, so a design document and its PHP factory method compare 1:1.
 */

use App\Domains\Editor\DTOs\EnvelopeAppearanceData;
use App\Domains\Editor\DTOs\SceneBackdropData;
use App\Domains\Editor\Enums\AssetType;
use App\Domains\Editor\Enums\CardMaterialPreset;
use App\Domains\Editor\Enums\TemplatePreset;
use App\Domains\Editor\Services\SvgAssetValidator;
use App\Domains\Editor\Services\TemplateDesignFactory;
use App\Domains\Editor\Services\TemplateScripture;
use App\Domains\Events\Enums\EventCategory;
use Database\Seeders\TemplateLibrarySeeder;

[, $project] = $argv + [null, null];
if ($project === null) {
    fwrite(STDERR, "usage: php project-data.php <methaqProjectDir>\n");
    exit(2);
}
$project = rtrim($project, '/');
require $project.'/vendor/autoload.php';

/** @return list<array<string, mixed>> */
function seederDefinitions(string $method): array
{
    $seeder = new TemplateLibrarySeeder(new SvgAssetValidator, new TemplateDesignFactory);

    return (new ReflectionMethod($seeder, $method))->invoke($seeder);
}

$editorSource = static fn (string $relative): string => (string) @file_get_contents($project.'/resources/js/Domains/Editor/'.$relative);

// Fonts: the library fonts the seeder defines, in seeder order.
preg_match('/variableFontSlugs = new Set\(\[([^\]]*)\]\)/', $editorSource('Hooks/useCanvas.ts'), $variableMatch);
preg_match_all("/'([a-z0-9-]+)'/", $variableMatch[1] ?? '', $variableSlugs);
$fonts = [];
$assetSlugs = [];
foreach (seederDefinitions('assetDefinitions') as $definition) {
    $assetSlugs[] = $definition['slug'];
    if ($definition['type'] !== AssetType::Font) {
        continue;
    }
    $fonts[$definition['slug']] = [
        'family' => $definition['metadata']['fontFamily'],
        'file' => substr($definition['original_path'], strlen('editor/assets/v1/fonts/')),
        'languages' => $definition['capabilities']['languages'],
        'variable' => in_array($definition['slug'], $variableSlugs[1], true),
        'placeholderId' => (string) (9_100_001 + count($fonts)),
    ];
}

// Verses: TemplateScripture is the only allowed Quran text; textSuggestions.ts says which occasions each one suits.
$suggestions = $editorSource('Services/textSuggestions.ts');
preg_match_all("/\{ id: '([^']+)', kind: '(verse|phrase)', categories: \[([^\]]*)\], text: \"((?:[^\"\\\\]|\\\\.)*)\"/u", $suggestions, $rows, PREG_SET_ORDER);
$suggestionCategories = [];
$phrases = [];
foreach ($rows as [, $id, $kind, $categoryList, $text]) {
    preg_match_all('/"([a-z_]+)"/', $categoryList, $categoryMatches);
    $suggestionCategories[$id] = $categoryMatches[1];
    if ($kind === 'phrase') {
        $phrases[] = ['id' => $id, 'categories' => $categoryMatches[1], 'text' => stripcslashes($text)];
    }
}
$verses = [];
foreach (TemplateScripture::VERSES as $id => $verse) {
    $verses[$id] = [
        'text' => $verse['text'],
        'source' => $verse['source'],
        'content' => TemplateScripture::content($id),
        'categories' => $suggestionCategories[$id] ?? [],
    ];
}

$templates = array_map(static fn (array $definition): array => [
    'slug' => $definition['slug'],
    'name' => $definition['name'],
    'category' => $definition['category']->value,
    'thumbnail' => $definition['thumbnail_path'],
], seederDefinitions('templateDefinitions'));

echo json_encode([
    'fonts' => $fonts,
    'templateAudio' => [TemplateDesignFactory::HENNA_AUDIO_SLUG => ['placeholderId' => '9200001', 'categories' => ['henna']]],
    'verses' => $verses,
    'phrases' => $phrases,
    'categories' => array_map(static fn (EventCategory $category): array => ['value' => $category->value, 'label' => $category->label()], EventCategory::cases()),
    'templates' => $templates,
    'templatePresets' => array_map(static fn (TemplatePreset $preset): string => $preset->value, TemplatePreset::cases()),
    'assetSlugs' => [...$assetSlugs, 'audio-methaq-chime', TemplateDesignFactory::HENNA_AUDIO_SLUG],
    'envelopeStyles' => EnvelopeAppearanceData::STYLES,
    'sealStyles' => EnvelopeAppearanceData::SEAL_STYLES,
    'backdrops' => (new ReflectionClassConstant(SceneBackdropData::class, 'PRESETS'))->getValue(),
    'materials' => array_map(static fn (CardMaterialPreset $preset): string => $preset->value, CardMaterialPreset::cases()),
    'coverDecorations' => ['none', 'floral', 'halo'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
