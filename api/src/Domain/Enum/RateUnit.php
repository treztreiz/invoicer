<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum RateUnit: string
{
    case HOURLY = 'HOURLY';
    case DAILY = 'DAILY';
}
