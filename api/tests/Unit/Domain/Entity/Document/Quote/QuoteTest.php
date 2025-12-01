<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Document\Quote;

use App\Domain\Entity\Document\Quote\Quote;
use App\Domain\Enum\QuoteStatus;
use App\Domain\Exception\DocumentRuleViolationException;
use App\Domain\Exception\DocumentTransitionException;
use App\Domain\Payload\Quote\QuotePayload;
use App\Domain\ValueObject\VatRate;
use App\Tests\Factory\Customer\CustomerFactory;
use App\Tests\Factory\ValueObject\CompanyFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @testType solitary-unit
 */
final class QuoteTest extends TestCase
{
    use Factories;

    private Quote $quote;

    protected function setUp(): void
    {
        $this->quote = static::createQuote();
    }

    public function test_send_moves_to_sent(): void
    {
        $sentAt = new \DateTimeImmutable('2025-03-01');

        $this->quote->send($sentAt);

        static::assertSame(QuoteStatus::SENT, $this->quote->status);
        static::assertSame($sentAt, $this->quote->sentAt);
    }

    public function test_send_only_allowed_from_draft(): void
    {
        $this->quote->send(new \DateTimeImmutable());

        static::expectException(DocumentTransitionException::class);
        $this->quote->send(new \DateTimeImmutable());
    }

    public function test_accept(): void
    {
        $this->quote->send(new \DateTimeImmutable('2025-03-01'));
        $acceptedAt = new \DateTimeImmutable('2025-03-05');

        $this->quote->markAccepted($acceptedAt);

        static::assertSame(QuoteStatus::ACCEPTED, $this->quote->status);
        static::assertSame($acceptedAt, $this->quote->acceptedAt);
        static::assertNull($this->quote->rejectedAt);
    }

    public function test_reject(): void
    {
        $this->quote->send(new \DateTimeImmutable('2025-03-01'));
        $rejectedAt = new \DateTimeImmutable('2025-03-05');

        $this->quote->markRejected($rejectedAt);

        static::assertSame(QuoteStatus::REJECTED, $this->quote->status);
        static::assertSame($rejectedAt, $this->quote->rejectedAt);
        static::assertNull($this->quote->acceptedAt);
    }

    public function test_link_converted_invoice_only_when_accepted(): void
    {
        $this->quote->send(new \DateTimeImmutable());
        $this->quote->markAccepted(new \DateTimeImmutable('+1 day'));

        $invoiceId = Uuid::v7();
        $this->quote->linkConvertedInvoice($invoiceId);

        static::assertEquals($invoiceId, $this->quote->convertedInvoiceId);
    }

    public function test_link_converted_invoice_requires_accepted_status(): void
    {
        $this->quote->send(new \DateTimeImmutable());

        static::expectException(DocumentRuleViolationException::class);
        $this->quote->linkConvertedInvoice(Uuid::v7());
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function createQuote(): Quote
    {
        $customer = CustomerFactory::build()->create();

        return Quote::fromPayload(
            payload: new QuotePayload(
                title: 'Sample quote',
                subtitle: null,
                customer: $customer,
                currency: 'EUR',
                vatRate: new VatRate('20'),
                linesPayload: []
            ),
            customer: $customer,
            company: CompanyFactory::createOne()
        );
    }
}
