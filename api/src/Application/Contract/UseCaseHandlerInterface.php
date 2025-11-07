<?php

declare(strict_types=1);

namespace App\Application\Contract;

/**
 * @doc Handles a use-case input/query and returns an output or list of outputs.
 *
 * @template T1 of object
 * @template T2 of object|null
 */
interface UseCaseHandlerInterface
{
    /**
     * @param T1 $data
     *
     * @return T2|array<T2>|null
     */
    public function handle(object $data): object|array|null;
}
