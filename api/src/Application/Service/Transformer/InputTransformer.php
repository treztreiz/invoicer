<?php

declare(strict_types=1);

namespace App\Application\Service\Transformer;

use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;
use App\Domain\Service\MoneyMath;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\VatRate;
use Symfony\Component\Uid\Uuid;

class InputTransformer
{
    private function __construct()
    {
    }

    // UUID ////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function uuid(mixed $value, object $source): ?Uuid
    {
        if (null === $value) {
            return null;
        }

        if (false === Uuid::isValid($value)) {
            throw new \InvalidArgumentException(sprintf('Value "%s" is not a valid Uuid.', $value));
        }

        return Uuid::fromString($value);
    }

    // VALUE OBJECT ////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function name(mixed $value, object $source): Name
    {
        if (false === property_exists($source, 'firstName')) {
            throw new \InvalidArgumentException(sprintf('The object "%s" must have the property $firstName.', get_debug_type($source)));
        }

        if (false === property_exists($source, 'lastName')) {
            throw new \InvalidArgumentException(sprintf('The object "%s" must have the property $lastName.', get_debug_type($source)));
        }

        return new Name($source->firstName, $source->lastName);
    }

    public static function contact(mixed $value, object $source): Contact
    {
        if (false === property_exists($source, 'email')) {
            throw new \InvalidArgumentException(sprintf('The object "%s" must have the property $email.', get_debug_type($source)));
        }

        if (false === property_exists($source, 'phone')) {
            throw new \InvalidArgumentException(sprintf('The object "%s" must have the property $phone.', get_debug_type($source)));
        }

        return new Contact($source->email, $source->phone);
    }

    // NUMBER //////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function percentage(mixed $value, object $source): string
    {
        return MoneyMath::decimal($value);
    }

    public static function vatRate(mixed $value, object $source): VatRate
    {
        return new VatRate(MoneyMath::decimal($value));
    }

    public static function money(mixed $value, object $source): Money
    {
        return new Money(MoneyMath::decimal($value));
    }

    // DATE ////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function date(mixed $value, object $source): \DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        if (false === $parsed) {
            throw new \InvalidArgumentException('Date must use Y-m-d format.');
        }

        return $parsed;
    }

    public static function dateOptional(mixed $value, object $source): ?\DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return self::date($value, $source);
    }

    // ENUM ////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function recurrenceFrequency(mixed $value, object $source): RecurrenceFrequency
    {
        return RecurrenceFrequency::from($value);
    }

    public static function recurrenceEndStrategy(mixed $value, object $source): RecurrenceEndStrategy
    {
        return RecurrenceEndStrategy::from($value);
    }
}
