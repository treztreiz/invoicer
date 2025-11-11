<?php

namespace App\Application\Guard;

use App\Application\Exception\ResourceNotFoundException;
use App\Application\UseCase\Invoice\Command\AttachInvoiceInstallmentPlanCommand;
use App\Application\UseCase\Invoice\Command\AttachInvoiceRecurrenceCommand;
use App\Domain\Entity\Document\Invoice;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InvoiceGuard
{
    public static function assertFound(?Invoice $invoice, string $id): Invoice
    {
        if (!$invoice instanceof Invoice) {
            throw new ResourceNotFoundException('Invoice', $id);
        }

        return $invoice;
    }

    /** @param class-string $action */
    public static function guardAgainstScheduleConflicts(Invoice $invoice, string $action): Invoice
    {
        if (!in_array($action, [AttachInvoiceRecurrenceCommand::class, AttachInvoiceInstallmentPlanCommand::class], true)) {
            throw new \InvalidArgumentException(sprintf('Action "%s" is not allowed.', $action));
        }

        if (
            (AttachInvoiceRecurrenceCommand::class === $action && null !== $invoice->installmentPlan)
            || (AttachInvoiceInstallmentPlanCommand::class === $action && null !== $invoice->recurrence)
        ) {
            throw new BadRequestHttpException('Invoices cannot have both a recurrence and an installment plan.');
        }

        if (null !== $invoice->recurrenceSeedId || null !== $invoice->installmentSeedId) {
            throw new BadRequestHttpException('Generated invoices cannot attach new scheduling rules.');
        }

        return $invoice;
    }
}