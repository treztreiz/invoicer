<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Input\Mapper;

use App\Application\UseCase\Invoice\Input\InvoiceRecurrenceInput;
use App\Domain\Entity\Document\Invoice\InvoiceRecurrence;
use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;

final class InvoiceRecurrenceMapper
{
    public function map(InvoiceRecurrenceInput $input): InvoiceRecurrence
    {
        $frequency = RecurrenceFrequency::from($input->frequency);
        $strategy = RecurrenceEndStrategy::from($input->endStrategy);
        $anchorDate = $this->parseDate($input->anchorDate, 'anchorDate');

        $endDate = null;
        $occurrenceCount = null;

        if (RecurrenceEndStrategy::UNTIL_DATE === $strategy) {
            $endDate = $this->parseOptionalDate($input->endDate, 'endDate');

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

        return new InvoiceRecurrence(
            frequency: $frequency,
            interval: $input->interval,
            anchorDate: $anchorDate,
            endStrategy: $strategy,
            nextRunAt: null,
            endDate: $endDate,
            occurrenceCount: $occurrenceCount,
        );
    }

    private function parseDate(string $date, string $field): \DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if (false === $parsed) {
            throw new \InvalidArgumentException(sprintf('Field "%s" must use Y-m-d format.', $field));
        }

        return $parsed;
    }

    private function parseOptionalDate(?string $date, string $field): ?\DateTimeImmutable
    {
        if (null === $date) {
            return null;
        }

        return $this->parseDate($date, $field);
    }
}
