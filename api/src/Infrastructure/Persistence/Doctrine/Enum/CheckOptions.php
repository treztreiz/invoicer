<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Enum;

enum CheckOptions: string
{
    case DECLARED = 'app_checks';
    case EXISTING = 'app_checks_present';
}
