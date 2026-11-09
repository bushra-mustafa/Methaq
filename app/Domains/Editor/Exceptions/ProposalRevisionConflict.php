<?php

declare(strict_types=1);

namespace App\Domains\Editor\Exceptions;

use RuntimeException;

final class ProposalRevisionConflict extends RuntimeException
{
    /** @param array{document: array<string, mixed>, revision: int} $snapshot */
    public function __construct(public readonly array $snapshot)
    {
        parent::__construct('حُفظ تعديل أحدث لهذه المسودة.');
    }
}
