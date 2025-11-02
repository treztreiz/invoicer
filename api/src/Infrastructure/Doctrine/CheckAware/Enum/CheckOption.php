<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Enum;

enum CheckOption: string
{
    case DECLARED = 'app_declared_checks';
    case INTROSPECTED = 'app_introspected_checks';
}
