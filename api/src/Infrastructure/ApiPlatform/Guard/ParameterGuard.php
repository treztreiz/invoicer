<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Guard;

use ApiPlatform\Metadata\Parameters;
use ApiPlatform\State\ParameterNotFound;

class ParameterGuard
{
    private function __construct()
    {
    }

    public static function get(Parameters $parameters, string $key, mixed $default = null): mixed
    {
        if (false === $parameters->has($key)) {
            return $default;
        }

        $value = $parameters->get($key)->getValue();
        if ($value instanceof ParameterNotFound) {
            return $default;
        }

        return $value;
    }
}
