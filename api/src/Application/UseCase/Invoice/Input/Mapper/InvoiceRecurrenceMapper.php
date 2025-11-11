<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Input\Mapper;

use App\Application\Guard\DateGuard;
use App\Application\UseCase\Invoice\Input\InvoiceRecurrenceInput;
use App\Domain\DTO\InvoiceRecurrencePayload;
use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;

final class InvoiceRecurrenceMapper
{
    public function map(InvoiceRecurrenceInput $input): InvoiceRecurrencePayload
    {
        $frequency = RecurrenceFrequency::from($input->frequency);
        $strategy = RecurrenceEndStrategy::from($input->endStrategy);
        $anchorDate = DateGuard::parse($input->anchorDate, 'anchorDate');

        $endDate = null;
        $occurrenceCount = null;

        if (RecurrenceEndStrategy::UNTIL_DATE === $strategy) {
            $endDate = DateGuard::parseOptional($input->endDate, 'endDate');

            if (null === $endDate) {
                throw new \InvalidArgumentException('endDate is required when end strategy is "UNTIL_DATE".');
            }
        }

        if (RecurrenceEndStrategy::UNTIL_COUNT === $strategy) {
            $occurrenceCount = $input->occurrenceCount;

            if (null === $occurrenceCount) {
                throw new \InvalidArgumentException('occurrenceCount is required when end strategy is "UNTIL_COUNT".');
            }
        }

        if (RecurrenceEndStrategy::NEVER === $strategy) {
            $endDate = null;
            $occurrenceCount = null;
        }

        return new InvoiceRecurrencePayload(
            frequency: $frequency,
            interval: $input->interval,
            anchorDate: $anchorDate,
            endStrategy: $strategy,
            endDate: $endDate,
            occurrenceCount: $occurrenceCount,
        );
    }
}
