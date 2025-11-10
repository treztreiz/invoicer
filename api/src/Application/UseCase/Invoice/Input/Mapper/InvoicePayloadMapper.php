<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Input\Mapper;

use App\Application\Service\DocumentLineFactory;
use App\Application\Service\DocumentSnapshotFactory;
use App\Application\Service\MoneyMath;
use App\Application\UseCase\Invoice\Input\InvoiceInput;
use App\Domain\DTO\InvoicePayload;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\User\User;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VatRate;

final readonly class InvoicePayloadMapper
{
    public function __construct(
        private DocumentSnapshotFactory $snapshotFactory,
        private DocumentLineFactory $lineFactory,
        private MoneyMath $math,
    ) {
    }

    public function map(InvoiceInput $input, Customer $customer, User $user): InvoicePayload
    {
        $vatRate = new VatRate($this->math->decimal($input->vatRate, 2));

        $linePayloads = [];
        $totalNet = '0.00';
        $totalTax = '0.00';

        foreach ($input->lines as $index => $lineInput) {
            $linePayload = $this->lineFactory->fromInvoiceInput($lineInput, $vatRate->value, $index);
            $linePayloads[] = $linePayload;

            $totalNet = $this->math->add($totalNet, $linePayload->amount->net->value);
            $totalTax = $this->math->add($totalTax, $linePayload->amount->tax->value);
        }

        $total = new AmountBreakdown(
            net: new Money($totalNet),
            tax: new Money($totalTax),
            gross: new Money($this->math->add($totalNet, $totalTax)),
        );

        return new InvoicePayload(
            title: $input->title,
            subtitle: $input->subtitle,
            currency: $input->currency,
            vatRate: $vatRate,
            total: $total,
            lines: $linePayloads,
            customerSnapshot: $this->snapshotFactory->customerSnapshot($customer),
            companySnapshot: $this->snapshotFactory->companySnapshot($user),
            dueDate: new \DateTimeImmutable($input->dueDate),
        );
    }
}
