<?php

namespace App\Domain\ValueObject;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Embeddable]
final class Quantity
{
    public function __construct(
        #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 3)]
        private(set) string $value
    ) {
        $sanitized = $this->sanitize($value);
        $this->assertValue($sanitized);
        $this->value = $this->normalize($sanitized);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private function assertValue(string $value): void
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Quantity must be numeric.');
        }

        if ((float)$value < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative.');
        }

        if (!preg_match('/^\d+(\.\d{1,3})?$/', $value)) {
            throw new InvalidArgumentException('Quantity must have at most three decimal places.');
        }
    }

    private function normalize(string $value): string
    {
        return number_format((float)$value, 3, '.', '');
    }

    private function sanitize(string $value): string
    {
        return str_replace(',', '', trim($value));
    }
}
