<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum RecurrenceFrequency: string
{
    case MONTHLY = 'MONTHLY';
    case QUARTERLY = 'QUARTERLY';

    /** @return list<string> */
    public static function frequencies(): array
    {
        return array_map(static fn (self $frequency) => $frequency->value, self::cases());
    }
}
