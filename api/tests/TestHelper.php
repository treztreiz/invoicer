<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Tests;

use Symfony\Component\Uid\Uuid;

final class TestHelper
{
    /**
     * Generate a deterministic UUID based on a seed.
     */
    public static function generateUuid(int $seed): string
    {
        return Uuid::fromBinary(str_pad((string) $seed, 16, '0', STR_PAD_LEFT))->toRfc4122();
    }

    /**
     * Assign a deterministic UUID to an entity in tests.
     */
    public static function assignUuid(object $entity, string $uuid): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setAccessible(true);
        $property->setValue($entity, Uuid::fromString($uuid));
    }

    /**
     * Force-set a private/protected property via reflection.
     */
    public static function setProperty(object $entity, string $propertyName, mixed $value): void
    {
        $property = new \ReflectionProperty($entity, $propertyName);
        $property->setAccessible(true);
        $property->setValue($entity, $value);
    }
}
