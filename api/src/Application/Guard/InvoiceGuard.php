<?php

declare(strict_types=1);

namespace App\Application\Guard;

use App\Application\Exception\DomainRuleViolationException;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Enum\InvoiceScheduleType;
use App\Domain\Enum\InvoiceStatus;

class InvoiceGuard
{
    public static function assertDraft(Invoice $invoice): Invoice
    {
        if (InvoiceStatus::DRAFT !== $invoice->status) {
            throw new DomainRuleViolationException('Only draft invoices can be updated.');
        }

        return $invoice;
    }

    public static function guardAgainstScheduleConflicts(Invoice $invoice, InvoiceScheduleType $type): Invoice
    {
        if (
            (InvoiceScheduleType::RECURRENCE === $type && null !== $invoice->installmentPlan)
            || (InvoiceScheduleType::INSTALLMENT === $type && null !== $invoice->recurrence)
        ) {
            throw new DomainRuleViolationException('Invoices cannot have both a recurrence and an installment plan.');
        }

        if (null !== $invoice->recurrenceSeedId || null !== $invoice->installmentSeedId) {
            throw new DomainRuleViolationException('Generated invoices cannot attach new scheduling rules.');
        }

        return $invoice;
    }

    public static function assertHasRecurrence(Invoice $invoice): Invoice
    {
        if (null === $invoice->recurrence) {
            throw new DomainRuleViolationException('Invoice does not have a recurrence configured.');
        }

        return $invoice;
    }

    public static function assertCanAttachRecurrence(Invoice $invoice, bool $replaceExisting): Invoice
    {
        if (null !== $invoice->recurrence && !$replaceExisting) {
            throw new DomainRuleViolationException('Invoice already has a recurrence configured.');
        }

        return $invoice;
    }

    public static function assertHasInstallmentPlan(Invoice $invoice): Invoice
    {
        if (null === $invoice->installmentPlan) {
            throw new DomainRuleViolationException('Invoice does not have an installment plan.');
        }

        return $invoice;
    }

    public static function assertCanAttachInstallmentPlan(Invoice $invoice, bool $replaceExisting): Invoice
    {
        if (null !== $invoice->installmentPlan && !$replaceExisting) {
            throw new DomainRuleViolationException('Invoice already has an installment plan.');
        }

        return $invoice;
    }
}
