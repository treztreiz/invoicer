<?php

declare(strict_types=1);

namespace App\Application\Contract;

/**
 * Maps a transport payload (e.g. API DTO) into a use-case input object.
 */
interface InputMapperInterface
{
    public function fromPayload(object $payload): object;
}
