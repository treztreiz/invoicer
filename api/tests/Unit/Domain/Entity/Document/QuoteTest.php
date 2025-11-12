<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Document;

use App\Domain\DTO\QuotePayload;
use App\Domain\Entity\Document\Quote;
use App\Domain\Enum\QuoteStatus;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VatRate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * @testType solitary-unit
 */
final class QuoteTest extends TestCase
{
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

        static::expectException(\LogicException::class);
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

        static::expectException(\LogicException::class);
        $this->quote->linkConvertedInvoice(Uuid::v7());
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function createQuote(): Quote
    {
        return Quote::fromPayload(
            new QuotePayload(
                title: 'Sample quote',
                subtitle: null,
                currency: 'EUR',
                vatRate: new VatRate('20'),
                total: new AmountBreakdown(
                    net: new Money('100'),
                    tax: new Money('20'),
                    gross: new Money('120'),
                ),
                lines: [],
                customerSnapshot: ['name' => 'Client'],
                companySnapshot: ['name' => 'My Company']
            )
        );
    }
}
