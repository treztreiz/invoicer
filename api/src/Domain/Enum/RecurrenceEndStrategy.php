<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum RecurrenceEndStrategy: string
{
    case UNTIL_DATE = 'UNTIL_DATE';
    case UNTIL_COUNT = 'UNTIL_COUNT';
    case NEVER = 'NEVER';
}
