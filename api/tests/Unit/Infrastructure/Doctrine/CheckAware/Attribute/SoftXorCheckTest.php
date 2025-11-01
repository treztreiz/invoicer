<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Attribute;

use App\Infrastructure\Doctrine\CheckAware\Attribute\SoftXorCheck;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class SoftXorCheckTest extends TestCase
{
    public function test_construct_with_valid_properties(): void
    {
        $attribute = new SoftXorCheck(['foo', 'bar'], 'CUSTOM');

        static::assertSame(['foo', 'bar'], $attribute->properties);
        static::assertSame('CUSTOM', $attribute->name);
    }

    public function test_construct_with_too_few_properties_throws(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('SoftXorCheck requires at least 2 properties.');

        new SoftXorCheck(['only-one']);
    }
}
