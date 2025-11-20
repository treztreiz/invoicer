<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\DomainGuardException;
use App\Domain\ValueObject\Contact;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class ContactTest extends TestCase
{
    public function test_email_is_lowercased_and_trimmed(): void
    {
        $contact = new Contact('  USER@Example.COM  ', null);

        static::assertSame('user@example.com', $contact->email);
    }

    public function test_phone_whitespace_is_removed(): void
    {
        $contact = new Contact(null, '+33 1 23 45 67 89');

        static::assertSame('+33123456789', $contact->phone);
    }

    public function test_invalid_email_is_rejected(): void
    {
        $this->expectException(DomainGuardException::class);

        new Contact('not-an-email', null);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $this->expectException(DomainGuardException::class);

        new Contact(null, 'abc123');
    }
}
