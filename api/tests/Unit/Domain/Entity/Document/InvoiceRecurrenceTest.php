<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Document;

use App\Domain\Entity\Document\Invoice\InvoiceRecurrence;
use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;
use App\Domain\Payload\Document\Invoice\InvoiceRecurrencePayload;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class InvoiceRecurrenceTest extends TestCase
{
    public function test_from_payload_creates_entity(): void
    {
        $recurrence = static::createRecurrence();

        static::assertSame(RecurrenceFrequency::MONTHLY, $recurrence->frequency);
        static::assertSame(1, $recurrence->interval);
        static::assertEquals(new \DateTimeImmutable('2025-01-01'), $recurrence->anchorDate);
        static::assertSame(RecurrenceEndStrategy::UNTIL_DATE, $recurrence->endStrategy);
    }

    public function test_interval_must_be_positive(): void
    {
        $payload = new InvoiceRecurrencePayload(
            frequency: RecurrenceFrequency::MONTHLY,
            interval: 0,
            anchorDate: new \DateTimeImmutable('2025-01-01'),
            endStrategy: RecurrenceEndStrategy::UNTIL_DATE,
            endDate: null,
            occurrenceCount: null,
        );

        $this->expectException(\InvalidArgumentException::class);

        InvoiceRecurrence::fromPayload($payload);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function createRecurrence(): InvoiceRecurrence
    {
        return InvoiceRecurrence::fromPayload(
            new InvoiceRecurrencePayload(
                frequency: RecurrenceFrequency::MONTHLY,
                interval: 1,
                anchorDate: new \DateTimeImmutable('2025-01-01'),
                endStrategy: RecurrenceEndStrategy::UNTIL_DATE,
                endDate: new \DateTimeImmutable('2025-12-31'),
                occurrenceCount: null,
            )
        );
    }
}
