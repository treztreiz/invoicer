<?php

namespace App\Tests\Unit\Domain\Entity\Document;

use App\Domain\Entity\Document\Invoice;
use App\Domain\Enum\InvoiceStatus;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VatRate;
use PHPUnit\Framework\TestCase;

final class InvoiceTest extends TestCase
{
    private Invoice $invoice;

    protected function setUp(): void
    {
        $this->invoice = $this->createInvoice();
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function test_issue_sets_status_and_dates(): void
    {
        $issuedAt = new \DateTimeImmutable('2025-06-10');
        $dueDate = new \DateTimeImmutable('2025-07-10');

        $this->invoice->issue($issuedAt, $dueDate);

        self::assertSame(InvoiceStatus::ISSUED, $this->invoice->status);
        self::assertSame($issuedAt, $this->invoice->issuedAt);
        self::assertSame($dueDate, $this->invoice->dueDate);
    }

    public function test_issue_rejected_when_not_draft(): void
    {
        $this->invoice->issue(new \DateTimeImmutable(), new \DateTimeImmutable('+1 day'));

        $this->expectException(\LogicException::class);
        $this->invoice->issue(new \DateTimeImmutable(), new \DateTimeImmutable('+1 day'));
    }

    public function test_mark_overdue_only_from_issued(): void
    {
        $this->expectException(\LogicException::class);
        $this->invoice->markOverdue();
    }

    public function test_mark_overdue(): void
    {
        $this->invoice->issue(new \DateTimeImmutable(), new \DateTimeImmutable('+1 day'));

        $this->invoice->markOverdue();

        self::assertSame(InvoiceStatus::OVERDUE, $this->invoice->status);
    }

    public function test_mark_paid(): void
    {
        $this->invoice->issue(new \DateTimeImmutable('2025-01-01'), new \DateTimeImmutable('2025-02-01'));

        $paidAt = new \DateTimeImmutable('2025-01-15');
        $this->invoice->markPaid($paidAt);

        self::assertSame(InvoiceStatus::PAID, $this->invoice->status);
        self::assertSame($paidAt, $this->invoice->paidAt);
    }

    public function test_void_draft(): void
    {
        $this->invoice->void();

        self::assertSame(InvoiceStatus::VOIDED, $this->invoice->status);
    }

    public function test_void_issued_with_payment_is_rejected(): void
    {
        $this->invoice->issue(new \DateTimeImmutable(), new \DateTimeImmutable('+1 day'));
        $this->invoice->markPaid(new \DateTimeImmutable('+1 day'));

        $this->expectException(\LogicException::class);
        $this->invoice->void();
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function createInvoice(): Invoice
    {
        return new Invoice(
            title: 'Sample invoice',
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
