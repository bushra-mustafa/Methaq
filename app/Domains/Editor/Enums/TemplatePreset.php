<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

enum TemplatePreset: string
{
    case PowderGold = 'powder-gold';
    case BurgundyNoir = 'burgundy-noir';
    case HeritageVertical = 'heritage-vertical';
    case GraduationEmerald = 'graduation-emerald';
}
