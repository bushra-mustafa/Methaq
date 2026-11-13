<?php

declare(strict_types=1);

namespace App\Domains\Payments\Services;

/** Formats LYD minor units (1 LYD = 1000 dirhams) for Arabic screens. */
final class LydAmount
{
    public const MINOR_UNITS_PER_DINAR = 1000;

    /** 25000 → «25 د.ل», 25500 → «25.5 د.ل». */
    public static function format(int $minor): string
    {
        return rtrim(rtrim(number_format($minor / self::MINOR_UNITS_PER_DINAR, 3, '.', ''), '0'), '.').' د.ل';
    }
}
