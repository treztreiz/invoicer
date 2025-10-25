<?php

namespace App\Tests\Unit\Domain\Entity\Document;

use App\Domain\Entity\Document\Quote;
use App\Domain\Enum\QuoteStatus;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VatRate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class QuoteTest extends TestCase
{
    private Quote $quote;

    protected function setUp(): void
    {
        $this->quote = $this->createQuote();
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function test_send_moves_to_sent(): void
    {
        $sentAt = new \DateTimeImmutable('2025-03-01');

        $this->quote->send($sentAt);

        self::assertSame(QuoteStatus::SENT, $this->quote->status);
        self::assertSame($sentAt, $this->quote->sentAt);
    }

    public function test_send_only_allowed_from_draft(): void
    {
        $this->quote->send(new \DateTimeImmutable());

        $this->expectException(\LogicException::class);
        $this->quote->send(new \DateTimeImmutable());
    }

    public function test_accept(): void
    {
        $this->quote->send(new \DateTimeImmutable('2025-03-01'));
        $acceptedAt = new \DateTimeImmutable('2025-03-05');

        $this->quote->markAccepted($acceptedAt);

        self::assertSame(QuoteStatus::ACCEPTED, $this->quote->status);
        self::assertSame($acceptedAt, $this->quote->acceptedAt);
        self::assertNull($this->quote->rejectedAt);
    }

    public function test_reject(): void
    {
        $this->quote->send(new \DateTimeImmutable('2025-03-01'));
        $rejectedAt = new \DateTimeImmutable('2025-03-05');

        $this->quote->markRejected($rejectedAt);

        self::assertSame(QuoteStatus::REJECTED, $this->quote->status);
        self::assertSame($rejectedAt, $this->quote->rejectedAt);
        self::assertNull($this->quote->acceptedAt);
    }

    public function test_link_converted_invoice_only_when_accepted(): void
    {
        $this->quote->send(new \DateTimeImmutable());
        $this->quote->markAccepted(new \DateTimeImmutable('+1 day'));

        $invoiceId = Uuid::v7();
        $this->quote->linkConvertedInvoice($invoiceId);

        self::assertEquals($invoiceId, $this->quote->convertedInvoiceId);
    }

    public function test_link_converted_invoice_requires_accepted_status(): void
    {
        $this->quote->send(new \DateTimeImmutable());

        $this->expectException(\LogicException::class);
        $this->quote->linkConvertedInvoice(Uuid::v7());
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function createQuote(): Quote
    {
        return new Quote(
            title: 'Sample quote',
            currency: 'EUR',
            vatRate: new VatRate('20'),
            customerSnapshot: ['name' => 'Client'],
            companySnapshot: ['name' => 'My Company'],
            subtotalNet: new Money('100'),
            taxTotal: new Money('20'),
            grandTotal: new Money('120')
        );
    }
}
