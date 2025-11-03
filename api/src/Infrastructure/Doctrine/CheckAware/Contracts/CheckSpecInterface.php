<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Contracts;

interface CheckSpecInterface
{
    public string $name {
        get;
    }
}
