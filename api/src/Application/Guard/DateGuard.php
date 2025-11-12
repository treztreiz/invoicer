<?php

declare(strict_types=1);

namespace App\Application\Guard;

final class DateGuard
{
    private function __construct()
    {
    }

    public static function parse(string $value, string $field, string $format = 'Y-m-d'): \DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat($format, $value);

        if (false === $parsed) {
            throw new \InvalidArgumentException(sprintf('Field "%s" must use %s format.', $field, $format));
        }

        return $parsed;
    }

    public static function parseOptional(?string $value, string $field, string $format = 'Y-m-d'): ?\DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return self::parse($value, $field, $format);
    }
}
