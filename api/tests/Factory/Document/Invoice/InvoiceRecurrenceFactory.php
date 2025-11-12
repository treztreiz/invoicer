<?php

declare(strict_types=1);

namespace App\Tests\Factory\Document\Invoice;

use App\Domain\Entity\Document\Invoice\InvoiceRecurrence;
use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;
use App\Tests\Factory\Common\BuildableFactoryTrait;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/** @extends PersistentObjectFactory<InvoiceRecurrence> */
class InvoiceRecurrenceFactory extends PersistentObjectFactory
{
    use BuildableFactoryTrait;

    public static function class(): string
    {
        return InvoiceRecurrence::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'frequency' => self::faker()->randomElement(RecurrenceFrequency::cases()),
            'interval' => self::faker()->numberBetween(1, 12),
            'anchorDate' => new \DateTimeImmutable(),
            'endStrategy' => RecurrenceEndStrategy::UNTIL_DATE,
        ];
    }
}
