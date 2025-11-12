<?php

declare(strict_types=1);

namespace App\Tests\Factory\ValueObject;

use App\Domain\ValueObject\AmountBreakdown;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<AmountBreakdown>
 */
final class AmountBreakdownFactory extends ObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return AmountBreakdown::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'gross' => MoneyFactory::new(['value' => '120']),
            'net' => MoneyFactory::new(['value' => '100']),
            'tax' => MoneyFactory::new(['value' => '20']),
        ];
    }
}
