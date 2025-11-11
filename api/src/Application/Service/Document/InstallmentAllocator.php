<?php

declare(strict_types=1);

namespace App\Application\Service\Document;

use App\Application\Service\MoneyMath;
use App\Domain\ValueObject\AmountBreakdown;

/**
 * @phpstan-type InstallmentShares array<array{percentage: numeric-string, net: numeric-string, tax: numeric-string, gross: numeric-string}>
 */
final class InstallmentAllocator
{
    /**
     * @param array<numeric-string> $percentages
     *
     * @return InstallmentShares
     */
    public function allocate(AmountBreakdown $totals, array $percentages, int $scale = 2): array
    {
        $this->assertTotal($percentages);

        $netShares = $this->allocateShares($totals->net->value, $percentages, $scale);
        $taxShares = $this->allocateShares($totals->tax->value, $percentages, $scale);
        $grossShares = $this->allocateShares($totals->gross->value, $percentages, $scale);

        $result = [];
        foreach ($percentages as $index => $percentage) {
            $result[$index] = [
                'percentage' => $percentage,
                'net' => $netShares[$index],
                'tax' => $taxShares[$index],
                'gross' => $grossShares[$index],
            ];
        }

        return $result;
    }

    /**
     * @param numeric-string        $amount
     * @param array<numeric-string> $percentages
     *
     * @return array<numeric-string>
     */
    private function allocateShares(string $amount, array $percentages, int $scale): array
    {
        $shares = [];
        $allocated = number_format(0, $scale, '.', '');
        $lastIndex = array_key_last($percentages);

        foreach ($percentages as $index => $percentage) {
            if ($index === $lastIndex) {
                $shares[$index] = MoneyMath::subtract($amount, $allocated, $scale);
                break;
            }

            $share = MoneyMath::percentage($amount, $percentage, $scale);
            $shares[$index] = $share;
            $allocated = MoneyMath::add($allocated, $share, $scale);
        }

        if (!isset($shares[$lastIndex])) {
            $shares[$lastIndex] = MoneyMath::subtract($amount, $allocated, $scale);
        }

        return $shares;
    }

    /**
     * @param array<numeric-string> $percentages
     */
    private function assertTotal(array $percentages): void
    {
        $total = array_reduce($percentages, fn (string $carry, string $value) => MoneyMath::add($carry, $value), '0.00');

        if (0 !== \bccomp($total, '100.00', 2)) {
            throw new \InvalidArgumentException('Installment percentages must total 100.');
        }
    }
}
