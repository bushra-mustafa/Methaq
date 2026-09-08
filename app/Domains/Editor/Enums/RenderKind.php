<?php

declare(strict_types=1);

namespace App\Domains\Editor\Enums;

enum RenderKind: string
{
    case Preview = 'preview';
    case Final = 'final';
}
