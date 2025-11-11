<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Input\Mapper;

use App\Application\Service\Document\DocumentLinePayloadFactory;
use App\Application\Service\Document\DocumentSnapshotFactory;
use App\Application\Service\MoneyMath;
use App\Application\UseCase\Invoice\Input\InvoiceInput;
use App\Domain\DTO\InvoicePayload;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\User\User;
use App\Domain\ValueObject\VatRate;

final readonly class InvoicePayloadMapper
{
    public function __construct(
        private DocumentSnapshotFactory $snapshotFactory,
        private DocumentLinePayloadFactory $linePayloadFactory,
    ) {
    }

    public function map(InvoiceInput $input, Customer $customer, User $user): InvoicePayload
    {
        $vatRate = new VatRate(MoneyMath::decimal($input->vatRate));
        $linesResult = $this->linePayloadFactory->build($input->lines, $vatRate->value);

        return new InvoicePayload(
            title: $input->title,
            subtitle: $input->subtitle,
            currency: $input->currency,
            vatRate: $vatRate,
            total: $linesResult->total,
            lines: $linesResult->lines,
            customerSnapshot: $this->snapshotFactory->customerSnapshot($customer),
            companySnapshot: $this->snapshotFactory->companySnapshot($user),
            dueDate: new \DateTimeImmutable($input->dueDate),
        );
    }
}
