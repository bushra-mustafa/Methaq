<?php

declare(strict_types=1);

namespace App\Domains\Payments\Services;

use Illuminate\Support\Facades\DB;

/** Prices an administrator sets from the panel, with the server config as the starting value. */
final class PricingSettings
{
    public const BLANK_CARD_KEY = 'pricing.blank_card_minor';

    public const MAXIMUM_MINOR = 100_000_000;

    /** What starting from a blank card costs, in LYD minor units. Zero means the blank card is not offered. */
    public function blankCardPriceMinor(): int
    {
        $stored = DB::table('settings')->where('key', self::BLANK_CARD_KEY)->value('value');

        return is_numeric($stored) ? max(0, (int) $stored) : max(0, (int) config('methaq.payment.blank_card_price_minor', 0));
    }

    public function setBlankCardPriceMinor(int $minor): void
    {
        $now = now();
        DB::table('settings')->updateOrInsert(
            ['key' => self::BLANK_CARD_KEY],
            ['value' => (string) $minor, 'created_at' => $now, 'updated_at' => $now],
        );
    }
}
