<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Enum;

enum CheckOption: string
{
    case DESIRED = 'app_checks';
    case EXISTING = 'app_checks_present';
}
