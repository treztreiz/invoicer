<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Input\Mapper;

use App\Application\Service\DocumentLineFactory;
use App\Application\Service\DocumentSnapshotFactory;
use App\Application\Service\MoneyMath;
use App\Application\UseCase\Quote\Input\QuoteInput;
use App\Domain\DTO\QuotePayload;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\User\User;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VatRate;

final readonly class QuotePayloadMapper
{
    public function __construct(
        private DocumentSnapshotFactory $snapshotFactory,
        private DocumentLineFactory $lineFactory,
        private MoneyMath $math,
    ) {
    }

    public function map(QuoteInput $input, Customer $customer, User $user): QuotePayload
    {
        $vatRate = new VatRate($this->math->decimal($input->vatRate, 2));

        $linePayloads = [];
        $totalNet = '0.00';
        $totalTax = '0.00';

        foreach ($input->lines as $index => $lineInput) {
            $linePayload = $this->lineFactory->fromQuoteInput($lineInput, $vatRate->value, $index);
            $linePayloads[] = $linePayload;

            $totalNet = $this->math->add($totalNet, $linePayload->amount->net->value);
            $totalTax = $this->math->add($totalTax, $linePayload->amount->tax->value);
        }

        $total = new AmountBreakdown(
            net: new Money($totalNet),
            tax: new Money($totalTax),
            gross: new Money($this->math->add($totalNet, $totalTax)),
        );

        return new QuotePayload(
            title: $input->title,
            subtitle: $input->subtitle,
            currency: $input->currency,
            vatRate: $vatRate,
            total: $total,
            lines: $linePayloads,
            customerSnapshot: $this->snapshotFactory->customerSnapshot($customer),
            companySnapshot: $this->snapshotFactory->companySnapshot($user),
        );
    }
}
