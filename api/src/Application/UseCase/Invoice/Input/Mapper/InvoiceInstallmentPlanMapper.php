<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Input\Mapper;

use App\Application\UseCase\Invoice\Input\InvoiceInstallmentInput;
use App\Application\UseCase\Invoice\Input\InvoiceInstallmentPlanInput;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;

final class InvoiceInstallmentPlanMapper
{
    public function map(InvoiceInstallmentPlanInput $input, Invoice $invoice): InstallmentPlan
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

        $percentages = array_map(fn (InvoiceInstallmentInput $installment) => $this->decimal($installment->percentage, 2), $installments);
        $this->assertPercentagesTotal($percentages);

        $plan = new InstallmentPlan();

        $netShares = $this->allocateByPercent($invoice->total->net->value, $percentages);
        $taxShares = $this->allocateByPercent($invoice->total->tax->value, $percentages);
        $grossShares = $this->allocateByPercent($invoice->total->gross->value, $percentages);

        foreach ($installments as $index => $installmentInput) {
            $plan->addInstallment(
                position: $index,
                percentage: $percentages[$index],
                amount: new AmountBreakdown(
                    net: new Money($netShares[$index]),
                    tax: new Money($taxShares[$index]),
                    gross: new Money($grossShares[$index]),
                ),
                dueDate: $this->parseOptionalDate($installmentInput->dueDate, sprintf('installments[%d].dueDate', $index)),
            );
        }

        return $plan;
    }

    /** @param array<string, mixed> $data */
    private function hydrateInstallmentInput(array $data): InvoiceInstallmentInput
    {
        return new InvoiceInstallmentInput(
            percentage: (float) ($data['percentage'] ?? 0),
            dueDate: isset($data['dueDate']) ? (string) $data['dueDate'] : null,
        );
    }

    /** @return numeric-string */
    private function decimal(float $value, int $scale = 2): string
    {
        return number_format($value, $scale, '.', '');
    }

    /**
     * @param numeric-string        $amount
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
                $shares[$index] = $this->subtract($amount, $allocated, $scale);

                break;
            }

            $share = $this->percentage($amount, $percentage, $scale);
            $shares[$index] = $share;
            $allocated = $this->add($allocated, $share, $scale);
        }

        if (!isset($shares[$lastIndex])) {
            $shares[$lastIndex] = $this->subtract($amount, $allocated, $scale);
        }

        return $shares;
    }

    /**
     * @param numeric-string $amount
     * @param numeric-string $percent
     *
     * @return numeric-string
     */
    private function percentage(string $amount, string $percent, int $scale = 2): string
    {
        if ('0.00' === $amount || '0.00' === $percent) {
            return number_format(0, $scale, '.', '');
        }

        $multiplied = \bcmul($amount, $percent, $scale + 4);

        return \bcdiv($multiplied, '100', $scale);
    }

    /**
     * @param numeric-string $left
     * @param numeric-string $right
     *
     * @return numeric-string
     */
    private function add(string $left, string $right, int $scale = 2): string
    {
        return \bcadd($left, $right, $scale);
    }

    /**
     * @param numeric-string $left
     * @param numeric-string $right
     *
     * @return numeric-string
     */
    private function subtract(string $left, string $right, int $scale = 2): string
    {
        return \bcsub($left, $right, $scale);
    }

    /**
     * @param array<numeric-string> $percentages
     */
    private function assertPercentagesTotal(array $percentages): void
    {
        $total = array_reduce($percentages, fn (string $carry, string $value) => \bcadd($carry, $value, 2), '0.00');

        if (0 !== \bccomp($total, '100.00', 2)) {
            throw new \InvalidArgumentException('Installment percentages must total 100.');
        }
    }

    private function parseOptionalDate(?string $date, string $field): ?\DateTimeImmutable
    {
        if (null === $date || '' === $date) {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if (false === $parsed) {
            throw new \InvalidArgumentException(sprintf('Field "%s" must use Y-m-d format.', $field));
        }

        return $parsed;
    }
}
