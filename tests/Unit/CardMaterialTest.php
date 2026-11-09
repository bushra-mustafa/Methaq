<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Editor\DTOs\EnvelopeAppearanceData;
use App\Domains\Editor\Enums\CardMaterialPreset;
use App\Domains\Editor\Services\BlankDesignFactory;
use App\Domains\Editor\Services\DesignDocumentHydrator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CardMaterialTest extends TestCase
{
    public function test_old_documents_and_all_materials_roundtrip_through_storage(): void
    {
        $legacy = (new BlankDesignFactory)->make()->toArray();
        $hydrator = new DesignDocumentHydrator;
        self::assertSame($legacy, $hydrator->hydrate($legacy)->toArray());
        foreach (CardMaterialPreset::cases() as $preset) {
            $document = $legacy;
            $document['canvas']['material'] = ['preset' => $preset->value, 'version' => 1, 'intensity' => 0.65];
            $hydrated = $hydrator->hydrate($document);
            self::assertSame($document, $hydrated->toArray());
            self::assertSame($document['canvas'], $hydrated->toStorageColumns()['design_json']);
        }
    }

    public function test_all_envelope_and_seal_combinations_survive_the_full_document_contract(): void
    {
        $document = (new BlankDesignFactory)->make()->toArray();
        $document['scene']['opening'] = [
            'type' => 'envelope', 'durationMs' => 1800,
            'envelope' => [
                'presetId' => 'classic-fold', 'presetVersion' => 1,
                'paperColor' => ['source' => 'literal', 'value' => '#591b32'],
                'liningColor' => ['source' => 'literal', 'value' => '#ead0d0'],
                'sealColor' => ['source' => 'literal', 'value' => '#b69a50'],
                'monogram' => 'م',
            ],
        ];
        foreach (EnvelopeAppearanceData::STYLES as $style) {
            foreach (EnvelopeAppearanceData::SEAL_STYLES as $sealStyle) {
                $appearance = ['style' => $style, 'sealStyle' => $sealStyle, 'sealX' => 50, 'sealY' => 52, 'sealSize' => 20];
                $document['scene']['opening']['envelope']['appearance'] = $appearance;
                self::assertSame($appearance, (new DesignDocumentHydrator)->hydrate($document)->toArray()['scene']['opening']['envelope']['appearance']);
            }
        }
    }

    #[DataProvider('invalidMaterials')]
    public function test_invalid_materials_are_rejected(mixed $material): void
    {
        $document = (new BlankDesignFactory)->make()->toArray();
        $document['canvas']['material'] = $material;
        $this->expectException(InvalidArgumentException::class);
        (new DesignDocumentHydrator)->hydrate($document);
    }

    /** @return iterable<string, array{mixed}> */
    public static function invalidMaterials(): iterable
    {
        $valid = ['preset' => 'wood', 'version' => 1, 'intensity' => 0.65];
        yield 'null' => [null];
        foreach ([['preset' => 'https://example.com/wood.svg'], ['version' => 2], ['intensity' => -0.1], ['intensity' => 1.1], ['intensity' => '0.5'], ['intensity' => INF], ['url' => '/untrusted.svg']] as $index => $override) {
            yield (string) $index => [array_replace($valid, $override)];
        }
    }
}
