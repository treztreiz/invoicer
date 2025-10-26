<?php

declare(strict_types=1);

namespace App\Domain\Guard;

final class DomainGuard
{
    private function __construct()
    {
    }

    public static function nonEmpty(string $value, string $label = 'Value'): string
    {
        $trimmed = trim($value);

        if ('' === $trimmed) {
            throw new \InvalidArgumentException(sprintf('%s cannot be blank.', $label));
        }

        return $trimmed;
    }

    public static function optionalNonEmpty(?string $value, string $label): ?string
    {
        if (null === $value) {
            return null;
        }

        $trimmed = trim($value);

        return '' === $trimmed ? null : $trimmed;
    }

    public static function personName(string $value, string $label): string
    {
        $name = self::nonEmpty($value, sprintf('%s cannot be blank.', $label));

        if (!preg_match("/^[\p{L}'\-\s]+$/u", $name)) {
            throw new \InvalidArgumentException(sprintf('%s contains invalid characters.', $label));
        }

        return $name;
    }

    public static function email(?string $value, string $label = 'Email'): ?string
    {
        if (null === $value) {
            return null;
        }

        $normalized = strtolower(self::nonEmpty($value, sprintf('%s cannot be blank.', $label)));

        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(sprintf('%s is not valid.', $label));
        }

        return $normalized;
    }

    public static function phone(?string $value, string $label = 'Phone'): ?string
    {
        if (null === $value) {
            return null;
        }

        $normalized = preg_replace('/\s+/', '', self::nonEmpty($value, sprintf('%s cannot be blank.', $label)));

        if (null === $normalized || !preg_match('/^\+?[0-9().-]{6,20}$/', $normalized)) {
            throw new \InvalidArgumentException(sprintf('%s is not valid.', $label));
        }

        return $normalized;
    }

    public static function countryCode(string $value, string $label = 'Country code'): string
    {
        $code = strtoupper(self::nonEmpty($value, sprintf('%s cannot be blank.', $label)));

        if (!preg_match('/^[A-Z]{2}$/', $code)) {
            throw new \InvalidArgumentException(sprintf('%s must be a two-letter ISO 3166-1 alpha-2 code.', $label));
        }

        return $code;
    }

    public static function currency(string $value, string $label = 'Currency'): string
    {
        $code = strtoupper(self::nonEmpty($value, sprintf('%s code is required.', $label)));

        if (3 !== strlen($code)) {
            throw new \InvalidArgumentException(sprintf('%s must be a 3-letter ISO 4217 code.', $label));
        }

        return $code;
    }

    public static function nonNegativeInt(int $value, string $label = 'Value'): int
    {
        if ($value < 0) {
            throw new \InvalidArgumentException(sprintf('%s cannot be negative.', $label));
        }

        return $value;
    }

    /** @return numeric-string */
    public static function decimal(
        string $value,
        int $scale,
        string $label,
        bool $allowNegative = false,
        ?float $min = null,
        ?float $max = null,
    ): string {
        $normalized = str_replace(' ', '', trim($value));
        $normalized = self::normalizeDecimalSeparator($normalized);

        if ('' === $normalized) {
            throw new \InvalidArgumentException(sprintf('%s must be provided.', $label));
        }

        if (!is_numeric($normalized)) {
            throw new \InvalidArgumentException(sprintf('%s must be numeric.', $label));
        }

        $number = (float)$normalized;

        if (!$allowNegative && $number < 0) {
            throw new \InvalidArgumentException(sprintf('%s cannot be negative.', $label));
        }

        if (null !== $min && $number < $min) {
            throw new \InvalidArgumentException(sprintf('%s must be at least %s.', $label, $min));
        }

        if (null !== $max && $number > $max) {
            throw new \InvalidArgumentException(sprintf('%s must be %s or less.', $label, $max));
        }

        $parts = explode('.', $normalized);
        if (isset($parts[1])) {
            $decimals = rtrim($parts[1], '0');
            if (strlen($decimals) > $scale) {
                throw new \InvalidArgumentException(sprintf('%s must have at most %d decimal places.', $label, $scale));
            }
        }

        return number_format($number, $scale, '.', '');
    }

    private static function normalizeDecimalSeparator(string $value): string
    {
        if (str_contains($value, ',') && !str_contains($value, '.')) {
            return str_replace(',', '.', $value);
        }

        return str_replace(',', '', $value);
    }
}
