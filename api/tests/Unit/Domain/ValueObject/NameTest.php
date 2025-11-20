<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\DomainGuardException;
use App\Domain\ValueObject\Name;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class NameTest extends TestCase
{
    public function test_accepts_letters_and_separators(): void
    {
        $name = new Name('Jean-Paul', "O'Neill");

        static::assertSame('Jean-Paul', $name->firstName);
        static::assertSame("O'Neill", $name->lastName);
    }

    public function test_blank_first_name_is_rejected(): void
    {
        $this->expectException(DomainGuardException::class);

        new Name('   ', 'Doe');
    }

    public function test_invalid_characters_are_rejected(): void
    {
        $this->expectException(DomainGuardException::class);

        new Name('John123', 'Doe');
    }
}
