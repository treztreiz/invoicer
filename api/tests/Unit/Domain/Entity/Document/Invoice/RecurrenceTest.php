<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Document\Invoice;

use App\Domain\Entity\Document\Invoice\Recurrence;
use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;
use App\Domain\Exception\DomainGuardException;
use App\Domain\Payload\Invoice\Recurrence\RecurrencePayload;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class RecurrenceTest extends TestCase
{
    #[DataProvider('validStrategiesProvider')]
    public function test_from_payload_creates_entity(
        RecurrenceEndStrategy $endStrategy,
        ?\DateTimeImmutable $endDate,
        ?int $occurrenceCount,
    ): void {
        $recurrence = static::createRecurrence(
            new RecurrencePayload(
                frequency: RecurrenceFrequency::MONTHLY,
                interval: 1,
                anchorDate: new \DateTimeImmutable('2025-01-01'),
                endStrategy: $endStrategy,
                endDate: $endDate,
                occurrenceCount: $occurrenceCount,
            )
        );

        static::assertSame(RecurrenceFrequency::MONTHLY, $recurrence->frequency);
        static::assertSame(1, $recurrence->interval);
        static::assertEquals(new \DateTimeImmutable('2025-01-01'), $recurrence->anchorDate);
        static::assertSame($endStrategy, $recurrence->endStrategy);
        static::assertSame($endDate, $recurrence->endDate);
        static::assertSame($occurrenceCount, $recurrence->occurrenceCount);
    }

    public function test_interval_must_be_positive(): void
    {
        $this->expectException(DomainGuardException::class);
        $this->expectExceptionMessage('Recurrence interval must be greater than zero.');

        Recurrence::fromPayload(
            new RecurrencePayload(
                frequency: RecurrenceFrequency::MONTHLY,
                interval: 0,
                anchorDate: new \DateTimeImmutable('2025-01-01'),
                endStrategy: RecurrenceEndStrategy::NEVER,
                endDate: null,
                occurrenceCount: null,
            )
        );
    }

    public function test_occurrence_count_must_be_positive(): void
    {
        $this->expectException(DomainGuardException::class);
        $this->expectExceptionMessage('Occurrence count must be greater than zero.');

        Recurrence::fromPayload(
            new RecurrencePayload(
                frequency: RecurrenceFrequency::MONTHLY,
                interval: 1,
                anchorDate: new \DateTimeImmutable('2025-01-01'),
                endStrategy: RecurrenceEndStrategy::UNTIL_COUNT,
                endDate: null,
                occurrenceCount: 0,
            )
        );
    }

    #[DataProvider('validStrategiesProvider')]
    public function test_apply_payload_updates_entity(
        RecurrenceEndStrategy $endStrategy,
        ?\DateTimeImmutable $endDate,
        ?int $occurrenceCount,
    ): void {
        $recurrence = static::createRecurrence();
        $recurrence->applyPayload(
            new RecurrencePayload(
                frequency: RecurrenceFrequency::QUARTERLY,
                interval: 2,
                anchorDate: new \DateTimeImmutable(),
                endStrategy: $endStrategy,
                endDate: $endDate,
                occurrenceCount: $occurrenceCount,
            )
        );

        static::assertSame(RecurrenceFrequency::QUARTERLY, $recurrence->frequency);
        static::assertSame(2, $recurrence->interval);
        static::assertSame($endStrategy, $recurrence->endStrategy);
        static::assertSame($endDate, $recurrence->endDate);
        static::assertSame($occurrenceCount ? $occurrenceCount - 1 : null, $recurrence->occurrenceCount);
    }

    #[DataProvider('invalidStrategiesProvider')]
    public function test_updates_entity_end_strategy_with_invalid_properties_is_rejected(
        RecurrenceEndStrategy $endStrategy,
        ?\DateTimeImmutable $endDate,
        ?int $occurrenceCount,
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(sprintf('/%s/', $endStrategy->value));

        Recurrence::fromPayload(
            new RecurrencePayload(
                frequency: RecurrenceFrequency::QUARTERLY,
                interval: 2,
                anchorDate: new \DateTimeImmutable(),
                endStrategy: $endStrategy,
                endDate: $endDate,
                occurrenceCount: $occurrenceCount,
            )
        );
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function validStrategiesProvider(): iterable
    {
        yield 'NEVER' => [RecurrenceEndStrategy::NEVER, null, null];
        yield 'UNTIL_DATE' => [RecurrenceEndStrategy::UNTIL_DATE, new \DateTimeImmutable(), null];
        yield 'UNTIL_COUNT' => [RecurrenceEndStrategy::UNTIL_COUNT, null, 10];
    }

    public static function invalidStrategiesProvider(): iterable
    {
        yield 'NEVER with date' => [RecurrenceEndStrategy::NEVER, new \DateTimeImmutable(), null];
        yield 'NEVER with count' => [RecurrenceEndStrategy::NEVER, null, 1];
        yield 'UNTIL_DATE without date' => [RecurrenceEndStrategy::UNTIL_DATE, null, null];
        yield 'UNTIL_DATE with count' => [RecurrenceEndStrategy::UNTIL_DATE, new \DateTimeImmutable(), 1];
        yield 'UNTIL_COUNT with date' => [RecurrenceEndStrategy::UNTIL_COUNT, new \DateTimeImmutable(), 1];
        yield 'UNTIL_COUNT without count' => [RecurrenceEndStrategy::UNTIL_COUNT, null, null];
    }

    public static function createRecurrence(?RecurrencePayload $payload = null): Recurrence
    {
        $payload = $payload ?: new RecurrencePayload(
            frequency: RecurrenceFrequency::MONTHLY,
            interval: 1,
            anchorDate: new \DateTimeImmutable('2025-01-01'),
            endStrategy: RecurrenceEndStrategy::NEVER,
            endDate: null,
            occurrenceCount: null,
        );

        return Recurrence::fromPayload($payload);
    }
}
