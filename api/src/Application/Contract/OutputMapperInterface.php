<?php

declare(strict_types=1);

namespace App\Application\Contract;

/**
 * Maps domain entities/value objects into a output DTO or view model.
 */
interface OutputMapperInterface
{
    public function toOutput(object $model): object;
}
