<?php

declare(strict_types=1);

namespace Src\Application\Participation\OpenSession;

final readonly class OpenSessionResult
{
    public function __construct(
        public string $participantId,
        public string $displayName,
        public int $attemptsLeft,
        public string $token,
        public bool $isNew,
    ) {
    }
}
