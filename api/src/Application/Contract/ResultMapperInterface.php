<?php

declare(strict_types=1);

namespace App\Application\Contract;

/**
 * Maps domain entities/value objects into a result DTO or view model.
 */
interface ResultMapperInterface
{
    public function toResult(object $model): object;
}
