<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Output\Mapper;

use App\Application\UseCase\Document\Output\DocumentLineOutput;
use App\Application\UseCase\Invoice\Output\InvoiceInstallmentOutput;
use App\Application\UseCase\Invoice\Output\InvoiceInstallmentPlanOutput;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\InvoiceRecurrenceOutput;
use App\Application\UseCase\Invoice\Output\InvoiceTotalsOutput;
use App\Domain\Entity\Document\DocumentLine;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Entity\Document\Invoice\Installment;
use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use App\Domain\Entity\Document\Invoice\InvoiceRecurrence;

final class InvoiceOutputMapper
{
    /**
     * @param list<string> $availableActions
     */
    public function map(Invoice $invoice, array $availableActions = []): InvoiceOutput
    {
        return new InvoiceOutput(
            invoiceId: $invoice->id?->toRfc4122() ?? '',
            title: $invoice->title,
            subtitle: $invoice->subtitle,
            status: $invoice->status->value,
            currency: $invoice->currency,
            vatRate: $invoice->vatRate->value,
            total: $this->mapTotal($invoice),
            lines: $this->mapLines($invoice),
            customerSnapshot: $invoice->customerSnapshot,
            companySnapshot: $invoice->companySnapshot,
            issuedAt: $invoice->issuedAt?->format(\DateTimeInterface::ATOM),
            dueDate: $invoice->dueDate?->format('Y-m-d'),
            paidAt: $invoice->paidAt?->format(\DateTimeInterface::ATOM),
            recurrence: $this->mapRecurrence($invoice->recurrence),
            installmentPlan: $this->mapInstallmentPlan($invoice->installmentPlan),
            availableActions: $availableActions,
        );
    }

    private function mapTotal(Invoice $invoice): InvoiceTotalsOutput
    {
        return new InvoiceTotalsOutput(
            net: $invoice->total->net->value,
            tax: $invoice->total->tax->value,
            gross: $invoice->total->gross->value,
        );
    }

    /** @return list<DocumentLineOutput> */
    private function mapLines(Invoice $invoice): array
    {
        return array_values(
            array_map(
                fn (DocumentLine $line) => new DocumentLineOutput(
                    description: $line->description,
                    quantity: $line->quantity->value,
                    rateUnit: $line->rateUnit->value,
                    rate: $line->rate->value,
                    net: $line->amount->net->value,
                    tax: $line->amount->tax->value,
                    gross: $line->amount->gross->value,
                ),
                $invoice->lines->toArray()
            )
        );
    }

    private function mapRecurrence(?InvoiceRecurrence $recurrence): ?InvoiceRecurrenceOutput
    {
        if (null === $recurrence) {
            return null;
        }

        return new InvoiceRecurrenceOutput(
            frequency: $recurrence->frequency->value,
            interval: $recurrence->interval,
            anchorDate: $recurrence->anchorDate->format('Y-m-d'),
            endStrategy: $recurrence->endStrategy->value,
            nextRunAt: $recurrence->nextRunAt?->format(\DateTimeInterface::ATOM),
            endDate: $recurrence->endDate?->format('Y-m-d'),
            occurrenceCount: $recurrence->occurrenceCount,
        );
    }

    private function mapInstallmentPlan(?InstallmentPlan $plan): ?InvoiceInstallmentPlanOutput
    {
        if (null === $plan) {
            return null;
        }

        $installments = array_map(
            fn (Installment $installment) => new InvoiceInstallmentOutput(
                position: $installment->position,
                percentage: $installment->percentage,
                amount: new InvoiceTotalsOutput(
                    net: $installment->amount->net->value,
                    tax: $installment->amount->tax->value,
                    gross: $installment->amount->gross->value,
                ),
                dueDate: $installment->dueDate?->format('Y-m-d'),
            ),
            $plan->installments()
        );

        return new InvoiceInstallmentPlanOutput(
            installmentPlanId: $plan->id?->toRfc4122() ?? '',
            installments: $installments,
        );
    }
}
