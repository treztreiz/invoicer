<?php

declare(strict_types=1);

namespace App\Application\UseCase\Me\Mapper;

use App\Application\Contract\CommandMapperInterface;
use App\Application\UseCase\Me\Input\MeInput;

final class MeCommandMapper implements CommandMapperInterface
{
    public function fromPayload(object $payload): MeInput
    {
        if (!$payload instanceof MeInput) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s.', MeInput::class, $payload::class));
        }

        // The payload already carries the command structure (thanks to Api Platform denormalization).
        // Returning the same instance keeps mutations (like userId assignment) possible downstream.
        return $payload;
    }
}
