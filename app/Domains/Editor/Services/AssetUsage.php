<?php

declare(strict_types=1);

namespace App\Domains\Editor\Services;

use App\Domains\Editor\Models\TemplateAsset;
use Illuminate\Support\Facades\DB;

/** Whether a library asset is still needed by a design, a template, a template link or a collection. */
final class AssetUsage
{
    /** Design columns that name assets as `"assetId": "<id>"`. */
    private const JSON_COLUMNS = [
        'event_designs' => ['design_json', 'scene_json', 'published_scene_json'],
        'templates' => ['default_design_json', 'default_scene_json'],
    ];

    public function isUsed(TemplateAsset $asset): bool
    {
        $id = (int) $asset->getKey();

        if (DB::table('asset_collection_items')->where('template_asset_id', $id)->exists()
            || DB::table('template_asset_links')->where('template_asset_id', $id)->exists()) {
            return true;
        }

        $pattern = '"assetId": ?"'.$id.'"';
        foreach (self::JSON_COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                if (DB::table($table)->whereNotNull($column)->whereRaw("CAST(`{$column}` AS CHAR) REGEXP ?", [$pattern])->exists()) {
                    return true;
                }
            }
        }

        return false;
    }
}
