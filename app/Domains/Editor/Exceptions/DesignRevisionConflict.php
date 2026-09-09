<?php

declare(strict_types=1);

namespace App\Domains\Editor\Exceptions;

use App\Domains\Editor\DTOs\DesignSnapshotData;
use RuntimeException;

final class DesignRevisionConflict extends RuntimeException
{
    public function __construct(public readonly DesignSnapshotData $snapshot)
    {
        parent::__construct('The design was updated from another session.');
    }
}
