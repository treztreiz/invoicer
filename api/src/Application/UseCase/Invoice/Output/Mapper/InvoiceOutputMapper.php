<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Output\Mapper;

use App\Application\UseCase\Invoice\Output\InvoiceLineOutput;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\InvoiceTotalsOutput;
use App\Domain\Entity\Document\DocumentLine;
use App\Domain\Entity\Document\Invoice;

final class InvoiceOutputMapper
{
    /**
     * @param list<string> $availableActions
     */
    public function map(Invoice $invoice, array $availableActions = []): InvoiceOutput
    {
        return new InvoiceOutput(
            id: $invoice->id?->toRfc4122() ?? '',
            title: $invoice->title,
            subtitle: $invoice->subtitle,
            status: $invoice->status->value,
            currency: $invoice->currency,
            vatRate: $invoice->vatRate->value,
            total: new InvoiceTotalsOutput(
                net: $invoice->total->net->value,
                tax: $invoice->total->tax->value,
                gross: $invoice->total->gross->value,
            ),
            lines: array_map(
                fn(DocumentLine $line) => new InvoiceLineOutput(
                    description: $line->description,
                    quantity: $line->quantity->value,
                    rateUnit: $line->rateUnit->value,
                    rate: $line->rate->value,
                    net: $line->amount->net->value,
                    tax: $line->amount->tax->value,
                    gross: $line->amount->gross->value,
                ),
                $invoice->lines->toArray()
            ),
            customerSnapshot: $invoice->customerSnapshot,
            companySnapshot: $invoice->companySnapshot,
            issuedAt: $invoice->issuedAt?->format(\DateTimeInterface::ATOM),
            dueDate: $invoice->dueDate?->format('Y-m-d'),
            paidAt: $invoice->paidAt?->format(\DateTimeInterface::ATOM),
            availableActions: $availableActions,
        );
    }
}
