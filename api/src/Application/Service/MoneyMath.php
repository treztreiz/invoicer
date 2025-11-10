<?php

declare(strict_types=1);

namespace App\Application\Service;

final class MoneyMath
{
    public function decimal(float $value, int $scale = 2): string
    {
        return number_format($value, $scale, '.', '');
    }

    public function add(string $left, string $right, int $scale = 2): string
    {
        return \bcadd($left, $right, $scale);
    }

    public function subtract(string $left, string $right, int $scale = 2): string
    {
        return \bcsub($left, $right, $scale);
    }

    public function multiply(string $left, string $right, int $scale = 2): string
    {
        return \bcmul($left, $right, $scale);
    }

    public function percentage(string $amount, string $rate, int $scale = 2): string
    {
        if ('0.00' === $amount || '0.00' === $rate) {
            return number_format(0, $scale, '.', '');
        }

        $multiplied = \bcmul($amount, $rate, $scale + 4);

        return \bcdiv($multiplied, '100', $scale);
    }
}
