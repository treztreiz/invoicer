<?php

namespace App\Domain\Enum;

enum RateUnit: string
{
    case HOURLY = 'HOURLY';
    case DAILY = 'DAILY';
}
