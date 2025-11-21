<?php

declare(strict_types=1);

namespace App\Application\Service\Transformer;

use Symfony\Component\Uid\Uuid;

class OutputTransformer
{
    private function __construct()
    {
    }

    // UUID ////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function uuid(?Uuid $value, object $source, ?object $target): string
    {
        return $value?->toRfc4122() ?: '';
    }

    // VALUE OBJECT ////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function valueObject(object $valueObject, object $source): mixed
    {
        if (false === property_exists($valueObject, 'value')) {
            throw new \InvalidArgumentException("Property 'value' does not exist.");
        }

        return $valueObject->value;
    }

    // DATE ////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function date(?\DateTimeInterface $date, object $source): ?string
    {
        return $date?->format('Y-m-d');
    }

    public static function dateTime(?\DateTimeInterface $date, object $source): ?string
    {
        return $date?->format(\DateTimeInterface::ATOM);
    }

    // ENUM ////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function backedEnum(\BackedEnum $enum, object $source): string|int
    {
        return $enum->value;
    }
}
