<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Input\Mapper;

use App\Application\Service\MoneyMath;
use App\Application\UseCase\Invoice\Input\InvoiceInstallmentInput;
use App\Application\UseCase\Invoice\Input\InvoiceInstallmentPlanInput;
use App\Application\Guard\DateGuard;
use App\Domain\DTO\InstallmentPayload;
use App\Domain\DTO\InstallmentPlanPayload;
use App\Domain\Entity\Document\Invoice;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;

final readonly class InvoiceInstallmentPlanMapper
{
    public function __construct(private MoneyMath $math)
    {
    }

    public function map(InvoiceInstallmentPlanInput $input, Invoice $invoice): InstallmentPlanPayload
    {
        if ([] === $input->installments) {
            throw new \InvalidArgumentException('At least one installment is required.');
        }

        $installments = [];
        foreach ($input->installments as $index => $item) {
            $installments[$index] = $item instanceof InvoiceInstallmentInput
                ? $item
                : $this->hydrateInstallmentInput($item);
        }

        $percentages = array_map(
            fn(InvoiceInstallmentInput $installment) => $this->math->decimal($installment->percentage, 2),
            $installments
        );

        $this->assertPercentagesTotal($percentages);

        $installmentPayloads = [];
        $netShares = $this->allocateByPercent($invoice->total->net->value, $percentages);
        $taxShares = $this->allocateByPercent($invoice->total->tax->value, $percentages);
        $grossShares = $this->allocateByPercent($invoice->total->gross->value, $percentages);

        foreach ($installments as $index => $installmentInput) {
            $installmentPayloads[] = new InstallmentPayload(
                position: $index,
                percentage: $percentages[$index],
                amount: new AmountBreakdown(
                    net: new Money($netShares[$index]),
                    tax: new Money($taxShares[$index]),
                    gross: new Money($grossShares[$index]),
                ),
                dueDate: DateGuard::parseOptional($installmentInput->dueDate, sprintf('installments[%d].dueDate', $index)),
            );
        }

        return new InstallmentPlanPayload($installmentPayloads);
    }

    /** @param array<string, mixed> $data */
    private function hydrateInstallmentInput(array $data): InvoiceInstallmentInput
    {
        return new InvoiceInstallmentInput(
            percentage: (float)($data['percentage'] ?? 0),
            dueDate: isset($data['dueDate']) ? (string)$data['dueDate'] : null,
        );
    }

    /**
     * @param numeric-string $amount
     * @param array<numeric-string> $percentages
     *
     * @return array<numeric-string>
     */
    private function allocateByPercent(string $amount, array $percentages, int $scale = 2): array
    {
        $shares = [];
        $allocated = number_format(0, $scale, '.', '');
        $lastIndex = array_key_last($percentages);

        foreach ($percentages as $index => $percentage) {
            if ($index === $lastIndex) {
                $shares[$index] = $this->math->subtract($amount, $allocated, $scale);
                break;
            }

            $share = $this->math->percentage($amount, $percentage, $scale);
            $shares[$index] = $share;
            $allocated = $this->math->add($allocated, $share, $scale);
        }

        if (!isset($shares[$lastIndex])) {
            $shares[$lastIndex] = $this->math->subtract($amount, $allocated, $scale);
        }

        return $shares;
    }

    /**
     * @param array<numeric-string> $percentages
     */
    private function assertPercentagesTotal(array $percentages): void
    {
        $total = array_reduce($percentages, fn(string $carry, string $value) => $this->math->add($carry, $value, 2), '0.00');

        if (0 !== \bccomp($total, '100.00', 2)) {
            throw new \InvalidArgumentException('Installment percentages must total 100.');
        }
    }

}
