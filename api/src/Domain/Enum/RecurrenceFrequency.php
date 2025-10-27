<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum RecurrenceFrequency: string
{
    case MONTHLY = 'MONTHLY';
    case QUARTERLY = 'QUARTERLY';
}
