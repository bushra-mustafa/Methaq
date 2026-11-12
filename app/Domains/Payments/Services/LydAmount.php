<?php

declare(strict_types=1);

namespace App\Domains\Payments\Services;

/** Formats LYD minor units (1 LYD = 1000 dirhams) for Arabic screens. */
final class LydAmount
{
    /** 25000 → «25 د.ل», 25500 → «25.5 د.ل». */
    public static function format(int $minor): string
    {
        return rtrim(rtrim(number_format($minor / 1000, 3, '.', ''), '0'), '.').' د.ل';
    }
}
