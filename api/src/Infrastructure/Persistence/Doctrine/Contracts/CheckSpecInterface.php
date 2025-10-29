<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Contracts;

interface CheckSpecInterface
{
    public string $name {
        get;
    }

    /** @var array<string, mixed> */
    public array $expr {
        get;
    }

    public bool $deferrable {
        get;
    }
}
