<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Document;

use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use App\Domain\Enum\InvoiceStatus;
use App\Domain\Payload\Document\InvoicePayload;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VatRate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * @testType solitary-unit
 */
final class InvoiceTest extends TestCase
{
    private Invoice $invoice;

    protected function setUp(): void
    {
        $this->invoice = static::createInvoice();
    }

    public function test_issue_sets_status_and_dates(): void
    {
        $issuedAt = new \DateTimeImmutable('2025-06-10');
        $dueDate = new \DateTimeImmutable('2025-07-10');

        $this->invoice->issue($issuedAt, $dueDate);

        static::assertSame(InvoiceStatus::ISSUED, $this->invoice->status);
        static::assertSame($issuedAt, $this->invoice->issuedAt);
        static::assertSame($dueDate, $this->invoice->dueDate);
    }

    public function test_issue_rejected_when_not_draft(): void
    {
        $this->invoice->issue(new \DateTimeImmutable(), new \DateTimeImmutable('+1 day'));

        static::expectException(\LogicException::class);
        $this->invoice->issue(new \DateTimeImmutable(), new \DateTimeImmutable('+1 day'));
    }

    public function test_mark_overdue_only_from_issued(): void
    {
        static::expectException(\LogicException::class);
        $this->invoice->markOverdue();
    }

    public function test_mark_overdue(): void
    {
        $this->invoice->issue(new \DateTimeImmutable(), new \DateTimeImmutable('+1 day'));

        $this->invoice->markOverdue();

        static::assertSame(InvoiceStatus::OVERDUE, $this->invoice->status);
    }

    public function test_mark_paid(): void
    {
        $this->invoice->issue(new \DateTimeImmutable('2025-01-01'), new \DateTimeImmutable('2025-02-01'));

        $paidAt = new \DateTimeImmutable('2025-01-15');
        $this->invoice->markPaid($paidAt);

        static::assertSame(InvoiceStatus::PAID, $this->invoice->status);
        static::assertSame($paidAt, $this->invoice->paidAt);
    }

    public function test_void_draft(): void
    {
        $this->invoice->void();

        static::assertSame(InvoiceStatus::VOIDED, $this->invoice->status);
    }

    public function test_void_issued_with_payment_is_rejected(): void
    {
        $this->invoice->issue(new \DateTimeImmutable(), new \DateTimeImmutable('+1 day'));
        $this->invoice->markPaid(new \DateTimeImmutable('+1 day'));

        static::expectException(\LogicException::class);
        $this->invoice->void();
    }

    public function test_attach_recurrence_rejected_when_installment_plan_exists(): void
    {
        $this->invoice->attachInstallmentPlan(new InstallmentPlan());

        static::expectException(\LogicException::class);
        $this->invoice->attachRecurrence(InvoiceRecurrenceTest::createRecurrence());
    }

    public function test_detach_recurrence_resets_reference(): void
    {
        $this->invoice->attachRecurrence(InvoiceRecurrenceTest::createRecurrence());

        $this->invoice->detachRecurrence();

        static::assertNull($this->invoice->recurrence);
    }

    public function test_detach_installment_plan(): void
    {
        $this->invoice->attachInstallmentPlan(new InstallmentPlan());

        $this->invoice->detachInstallmentPlan();

        static::assertNull($this->invoice->installmentPlan);
    }

    public function test_generated_from_recurrence_cannot_attach_recurrence(): void
    {
        $this->invoice->markGeneratedFromRecurrence(Uuid::v7());

        static::expectException(\LogicException::class);
        $this->invoice->attachRecurrence(InvoiceRecurrenceTest::createRecurrence());
    }

    public function test_generated_from_installment_cannot_attach_recurrence(): void
    {
        $this->invoice->markGeneratedFromInstallment(Uuid::v7());

        static::expectException(\LogicException::class);
        $this->invoice->attachRecurrence(InvoiceRecurrenceTest::createRecurrence());
    }

    public function test_generated_invoice_cannot_attach_installment_plan(): void
    {
        $this->invoice->markGeneratedFromRecurrence(Uuid::v7());

        static::expectException(\LogicException::class);
        $this->invoice->attachInstallmentPlan(new InstallmentPlan());
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function createInvoice(): Invoice
    {
        return Invoice::fromPayload(
            payload: new InvoicePayload(
                title: 'Sample invoice',
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
                companySnapshot: ['name' => 'My Company'],
                dueDate: null
            ),
            customer: static::createStub(Customer::class),
        );
    }
}
