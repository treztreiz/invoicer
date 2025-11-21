<?php

declare(strict_types=1);

namespace App\Tests\Factory\Document\Invoice;

use App\Domain\Entity\Document\Invoice\Recurrence;
use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;
use App\Tests\Factory\Common\BuildableFactoryTrait;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/** @extends PersistentObjectFactory<Recurrence> */
class RecurrenceFactory extends PersistentObjectFactory
{
    use BuildableFactoryTrait;

    #[\Override]
    public static function class(): string
    {
        return Recurrence::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'frequency' => self::faker()->randomElement(RecurrenceFrequency::cases()),
            'interval' => self::faker()->numberBetween(1, 12),
            'anchorDate' => new \DateTimeImmutable(),
            'endStrategy' => RecurrenceEndStrategy::NEVER,
        ];
    }
}
