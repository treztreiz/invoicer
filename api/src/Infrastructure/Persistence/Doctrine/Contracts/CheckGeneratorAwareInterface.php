<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Contracts;

interface CheckGeneratorAwareInterface
{
    public CheckGeneratorInterface $generator {
        get;
    }
}
