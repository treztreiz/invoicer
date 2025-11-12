<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Input\Mapper;

use App\Application\Guard\DateGuard;
use App\Application\Service\Document\InstallmentAllocator;
use App\Application\Service\MoneyMath;
use App\Application\UseCase\Invoice\Input\InvoiceInstallmentInput;
use App\Application\UseCase\Invoice\Input\InvoiceInstallmentPlanInput;
use App\Domain\DTO\InstallmentPayload;
use App\Domain\DTO\InstallmentPlanPayload;
use App\Domain\Entity\Document\Invoice;
use App\Domain\ValueObject\AmountBreakdown;

final readonly class InvoiceInstallmentPlanMapper
{
    public function __construct(private InstallmentAllocator $allocator)
    {
    }

    public function map(InvoiceInstallmentPlanInput $input, Invoice $invoice): InstallmentPlanPayload
    {
        if ([] === $input->installments) {
            throw new \InvalidArgumentException('At least one installment is required.');
        }

        $installments = [];
        foreach ($input->installments as $index => $item) {
            $installments[$index] = $item instanceof InvoiceInstallmentInput ? $item : $this->hydrateInstallmentInput($item);
        }

        $percentages = array_map(
            fn (InvoiceInstallmentInput $installment) => MoneyMath::decimal($installment->percentage),
            $installments
        );

        $installmentPayloads = $this->generateInstallmentPayloads($invoice, $installments, $percentages);

        return new InstallmentPlanPayload($installmentPayloads);
    }

    /** @param array<string, mixed> $data */
    private function hydrateInstallmentInput(array $data): InvoiceInstallmentInput
    {
        return new InvoiceInstallmentInput(
            percentage: (float) ($data['percentage'] ?? 0),
            dueDate: isset($data['dueDate']) ? (string) $data['dueDate'] : null,
        );
    }

    /**
     * @param array<int, InvoiceInstallmentInput> $installments
     * @param array<numeric-string>               $percentages
     *
     * @return list<InstallmentPayload>
     */
    private function generateInstallmentPayloads(Invoice $invoice, array $installments, array $percentages): array
    {
        $shares = $this->allocator->allocate($invoice->total, $percentages);

        $installmentPayloads = [];

        foreach ($installments as $index => $installmentInput) {
            $share = $shares[$index];
            $installmentPayloads[] = new InstallmentPayload(
                position: $index,
                percentage: $share['percentage'],
                amount: AmountBreakdown::fromValues($share['net'], $share['tax'], $share['gross']),
                dueDate: DateGuard::parseOptional($installmentInput->dueDate, sprintf('installments[%d].dueDate', $index)),
            );
        }

        return $installmentPayloads;
    }
}
