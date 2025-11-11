<?php

declare(strict_types=1);

namespace App\Application\Service;

final class InstallmentAllocator
{
    /**
     * @param array<numeric-string> $percentages
     *
     * @return array{shares: array<numeric-string>, percentages: array<numeric-string>}
     */
    public function allocate(string $amount, array $percentages, int $scale = 2): array
    {
        $this->assertTotal($percentages);

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

        return ['shares' => $shares, 'percentages' => $percentages];
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
