<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum RecurrenceEndStrategy: string
{
    case NEVER = 'NEVER';
    case UNTIL_DATE = 'UNTIL_DATE';
    case UNTIL_COUNT = 'UNTIL_COUNT';

    /** @return list<string> */
    public static function endStrategies(): array
    {
        return array_map(static fn (self $strategy) => $strategy->value, self::cases());
    }
}
