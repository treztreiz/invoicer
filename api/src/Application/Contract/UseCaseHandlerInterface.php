<?php

declare(strict_types=1);

namespace App\Application\Contract;

/**
 * Handles a use-case input and returns a result (usually a view model or DTO).
 */
interface UseCaseHandlerInterface
{
    public function handle(object $input): object;
}
