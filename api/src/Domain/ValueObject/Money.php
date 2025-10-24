<?php

namespace App\Domain\ValueObject;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Embeddable]
final class Money
{
    public function __construct(
        #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
        private(set) string $amount
    ) {
        $sanitized = $this->sanitize($amount);
        $this->assertAmount($sanitized);
        $this->amount = $this->normalize($sanitized);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function formatted(): string
    {
        return number_format((float)$this->amount, 2, '.', '');
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount;
    }

    private function assertAmount(string $amount): void
    {
        if (!is_numeric($amount)) {
            throw new InvalidArgumentException('Money amount must be numeric.');
        }

        if ((float)$amount < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative.');
        }

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException('Money amount must have at most two decimal places.');
        }
    }

    private function normalize(string $amount): string
    {
        return number_format((float)$amount, 2, '.', '');
    }

    private function sanitize(string $amount): string
    {
        return str_replace(',', '', trim($amount));
    }
}
