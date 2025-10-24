<?php

namespace App\Domain\ValueObject;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Embeddable]
final class VatRate
{
    public function __construct(
        #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
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
            throw new InvalidArgumentException('VAT rate must be numeric.');
        }

        $numeric = (float)$value;

        if ($numeric < 0 || $numeric > 100) {
            throw new InvalidArgumentException('VAT rate must be between 0 and 100.');
        }

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('VAT rate must have at most two decimal places.');
        }
    }

    private function normalize(string $value): string
    {
        return number_format((float)$value, 2, '.', '');
    }

    private function sanitize(string $value): string
    {
        return str_replace(',', '', trim($value));
    }
}
