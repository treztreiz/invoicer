<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum RateUnit: string
{
    case HOURLY = 'HOURLY';
    case DAILY = 'DAILY';

    /**
     * @return list<string>
     */
    public static function rateUnits(): array
    {
        return array_map(static fn (self $unit) => $unit->value, self::cases());
    }
}
