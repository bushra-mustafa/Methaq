<?php

declare(strict_types=1);

namespace App\Domains\Editor\Services;

use App\Domains\Editor\DTOs\DesignDocumentData;
use App\Domains\Editor\DTOs\ImageLayerData;
use App\Domains\Editor\DTOs\TextLayerData;
use App\Domains\Editor\Enums\AssetType;
use App\Domains\Editor\Exceptions\InvalidDesignAssetReference;
use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Models\TemplateAsset;

final class DesignAssetReferenceValidator
{
    public function assertValid(DesignDocumentData $document, EventDesign $currentDesign): void
    {
        $requirements = $this->requirements($document);
        if ($requirements === []) {
            return;
        }

        $assets = TemplateAsset::query()
            ->whereIn('id', array_keys($requirements))
            ->get()
            ->keyBy(static fn (TemplateAsset $asset): int => (int) $asset->getKey());
        $previouslyReferenced = array_flip($this->storedReferenceIds($currentDesign));

        foreach ($requirements as $id => $requirement) {
            $asset = $assets->get($id);
            if (! $asset instanceof TemplateAsset) {
                throw new InvalidDesignAssetReference('التصميم يشير إلى عنصر غير موجود.');
            }
            if ($asset->asset_version !== $requirement['version']) {
                throw new InvalidDesignAssetReference('إصدار أحد عناصر التصميم غير متاح.');
            }
            if (! $asset->is_active && ! isset($previouslyReferenced[$id])) {
                throw new InvalidDesignAssetReference('لا يمكن إضافة عنصر متوقف إلى التصميم.');
            }

            $validType = match ($requirement['usage']) {
                'font' => $asset->type === AssetType::Font,
                'audio' => $asset->type === AssetType::Audio,
                'image' => ! in_array($asset->type, [AssetType::Font, AssetType::Audio], true),
            };
            if (! $validType) {
                throw new InvalidDesignAssetReference('نوع أحد عناصر التصميم لا يطابق مكان استخدامه.');
            }
        }
    }

    /** @return array<int, array{version: int, usage: 'audio'|'font'|'image'}> */
    private function requirements(DesignDocumentData $document): array
    {
        $requirements = [];
        foreach ($document->canvas->layers as $layer) {
            $reference = match (true) {
                $layer->properties instanceof TextLayerData => [$layer->properties->font, 'font'],
                $layer->properties instanceof ImageLayerData => [$layer->properties->asset, 'image'],
                default => null,
            };
            if ($reference === null) {
                continue;
            }

            [$asset, $usage] = $reference;
            $id = (int) $asset->assetId;
            if (isset($requirements[$id]) && $requirements[$id]['usage'] !== $usage) {
                throw new InvalidDesignAssetReference('لا يمكن استخدام العنصر نفسه بأنواع غير متوافقة.');
            }
            $requirements[$id] = ['version' => $asset->version, 'usage' => $usage];
        }

        $audio = $document->scene->audio->asset;
        if ($audio !== null) {
            $id = (int) $audio->assetId;
            if (isset($requirements[$id]) && $requirements[$id]['usage'] !== 'audio') {
                throw new InvalidDesignAssetReference('لا يمكن استخدام العنصر نفسه للصوت والرسم.');
            }
            $requirements[$id] = ['version' => $audio->version, 'usage' => 'audio'];
        }

        return $requirements;
    }

    /** @return list<int> */
    private function storedReferenceIds(EventDesign $design): array
    {
        $ids = [];
        $layers = $design->design_json['layers'] ?? [];
        if (is_array($layers)) {
            foreach ($layers as $layer) {
                if (! is_array($layer)) {
                    continue;
                }
                $id = $layer['asset']['assetId'] ?? $layer['font']['assetId'] ?? null;
                if (is_string($id) && ctype_digit($id)) {
                    $ids[] = (int) $id;
                }
            }
        }

        $audioId = $design->scene_json['audio']['asset']['assetId'] ?? null;
        if (is_string($audioId) && ctype_digit($audioId)) {
            $ids[] = (int) $audioId;
        }

        return array_values(array_unique($ids));
    }
}
