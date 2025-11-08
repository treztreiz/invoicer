<?php

declare(strict_types=1);

namespace App\Application\Guard;

class TypeGuard
{
    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public static function assertClass(string $class, mixed $data): object
    {
        if (!$data instanceof $class) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s.', $class, get_debug_type($data)));
        }

        return $data;
    }
}
