<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Attribute;

use App\Infrastructure\Doctrine\CheckAware\Attribute\EnumCheck;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class EnumCheckTest extends TestCase
{
    public function test_construct_with_valid_arguments_does_not_throw(): void
    {
        $attribute = new EnumCheck(property: 'status', name: 'CHK_STATUS', enumFqcn: BackedStringEnumStub::class);

        static::assertSame('status', $attribute->property);
        static::assertSame('CHK_STATUS', $attribute->name);
        static::assertSame(BackedStringEnumStub::class, $attribute->enumFqcn);
    }

    public function test_empty_property_is_rejected(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('EnumCheck requires a non-empty property name.');

        new EnumCheck('');
    }

    public function test_empty_name_is_rejected(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('EnumCheck requires a non-empty constraint name.');

        new EnumCheck(property: 'status', name: '');
    }

    public function test_non_existing_enum_class_is_rejected(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('EnumCheck expects enumFqcn `Unknown\\Foo` to be an enum.');

        new EnumCheck(property: 'status', enumFqcn: 'Unknown\\Foo');
    }

    public function test_unit_enum_is_rejected(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('EnumCheck enumFqcn `%s` must be a backed enum.', UnitEnumStub::class));

        new EnumCheck(property: 'status', enumFqcn: UnitEnumStub::class);
    }
}

enum BackedStringEnumStub: string
{
    case A = 'a';
}

enum UnitEnumStub
{
    case A;
}
