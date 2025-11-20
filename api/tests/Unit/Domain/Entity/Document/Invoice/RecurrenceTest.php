<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Document\Invoice;

use App\Domain\Entity\Document\Invoice\Recurrence;
use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;
use App\Domain\Exception\DomainGuardException;
use App\Domain\Payload\Invoice\Recurrence\RecurrencePayload;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class RecurrenceTest extends TestCase
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
        $payload = new RecurrencePayload(
            frequency: RecurrenceFrequency::MONTHLY,
            interval: 0,
            anchorDate: new \DateTimeImmutable('2025-01-01'),
            endStrategy: RecurrenceEndStrategy::UNTIL_DATE,
            endDate: null,
            occurrenceCount: null,
        );

        $this->expectException(DomainGuardException::class);

        Recurrence::fromPayload($payload);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function createRecurrence(): Recurrence
    {
        return Recurrence::fromPayload(
            new RecurrencePayload(
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
