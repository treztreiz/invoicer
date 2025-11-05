<?php

declare(strict_types=1);

namespace App\Application\Contract;

/**
 * Maps a transport payload (e.g. API DTO) into a use-case command object.
 */
interface CommandMapperInterface
{
    public function fromPayload(object $payload): object;
}
