<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Input\Mapper;

use App\Application\Service\Document\DocumentLinePayloadFactory;
use App\Application\Service\Document\DocumentSnapshotFactory;
use App\Application\Service\MoneyMath;
use App\Application\UseCase\Quote\Input\QuoteInput;
use App\Domain\DTO\QuotePayload;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\User\User;
use App\Domain\ValueObject\VatRate;

final readonly class QuotePayloadMapper
{
    public function __construct(
        private DocumentSnapshotFactory $snapshotFactory,
        private DocumentLinePayloadFactory $linePayloadFactory,
    ) {
    }

    public function map(QuoteInput $input, Customer $customer, User $user): QuotePayload
    {
        $vatRate = new VatRate(MoneyMath::decimal($input->vatRate));
        $linesResult = $this->linePayloadFactory->build($input->lines, $vatRate->value);

        return new QuotePayload(
            title: $input->title,
            subtitle: $input->subtitle,
            currency: $input->currency,
            vatRate: $vatRate,
            total: $linesResult->total,
            lines: $linesResult->lines,
            customerSnapshot: $this->snapshotFactory->customerSnapshot($customer),
            companySnapshot: $this->snapshotFactory->companySnapshot($user),
        );
    }
}
