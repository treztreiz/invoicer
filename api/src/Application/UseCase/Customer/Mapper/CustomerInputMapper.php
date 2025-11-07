<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Mapper;

use App\Application\Contract\InputMapperInterface;
use App\Application\UseCase\Customer\Input\CustomerInput;

final class CustomerInputMapper implements InputMapperInterface
{
    public function fromPayload(object $payload): CustomerInput
    {
        if (!$payload instanceof CustomerInput) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s.', CustomerInput::class, $payload::class));
        }

        return $payload;
    }
}
