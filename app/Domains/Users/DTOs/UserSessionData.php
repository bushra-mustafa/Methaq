<?php

declare(strict_types=1);

namespace App\Domains\Users\DTOs;

use DateTimeImmutable;

final readonly class UserSessionData
{
    public function __construct(
        public string $fingerprint,
        public bool $isCurrentDevice,
        public ?string $ipAddress,
        public string $userAgent,
        public DateTimeImmutable $lastActiveAt,
    ) {}

    /** @return array{fingerprint: string, isCurrentDevice: bool, ipAddress: string|null, userAgent: string, lastActiveAt: string} */
    public function toArray(): array
    {
        return [
            'fingerprint' => $this->fingerprint,
            'isCurrentDevice' => $this->isCurrentDevice,
            'ipAddress' => $this->ipAddress,
            'userAgent' => $this->userAgent,
            'lastActiveAt' => $this->lastActiveAt->format(DATE_ATOM),
        ];
    }
}
