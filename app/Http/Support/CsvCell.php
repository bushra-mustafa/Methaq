<?php

declare(strict_types=1);

namespace App\Http\Support;

/** Keeps spreadsheet apps from running a cell as a formula. */
final class CsvCell
{
    /** A cell that starts like a formula would run in Excel; a leading apostrophe turns it back into text. */
    public static function safe(string $value): string
    {
        return $value !== '' && str_contains("=+-@\t\r", $value[0]) ? "'".$value : $value;
    }
}
