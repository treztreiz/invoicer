<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Entity\Document;

use App\Domain\Entity\Document\Invoice;
use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use App\Domain\Entity\Document\Invoice\InvoiceRecurrence;
use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VatRate;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @testType integration
 */
final class InvoiceSoftXorPersistenceTest extends KernelTestCase
{
    use ResetDatabase;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function test_invoice_with_only_recurrence_persists(): void
    {
        $invoice = $this->createInvoice();
        $invoice->attachRecurrence(
            new InvoiceRecurrence(
                RecurrenceFrequency::MONTHLY,
                interval: 1,
                anchorDate: new \DateTimeImmutable('2025-01-01'),
                endStrategy: RecurrenceEndStrategy::UNTIL_DATE,
                nextRunAt: null,
                endDate: new \DateTimeImmutable('2025-06-01'),
            )
        );

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        static::assertNotNull($invoice->id);
    }

    public function test_invoice_with_recurrence_and_installments_violates_soft_xor(): void
    {
        $invoice = $this->createInvoice();
        $invoice->attachRecurrence(
            new InvoiceRecurrence(
                RecurrenceFrequency::MONTHLY,
                interval: 1,
                anchorDate: new \DateTimeImmutable('2025-01-01'),
                endStrategy: RecurrenceEndStrategy::UNTIL_DATE,
                nextRunAt: null,
                endDate: new \DateTimeImmutable('2025-06-01'),
            )
        );

        $plan = new InstallmentPlan();
        $this->forceInstallmentPlan($invoice, $plan);

        $this->entityManager->persist($invoice);

        $this->expectException(DriverException::class);

        try {
            $this->entityManager->flush();
        } catch (DriverException $exception) {
            static::assertSame('23514', $exception->getSQLState());
            static::assertStringContainsString('CHK_INVOICE_SCHEDULE_XOR', $exception->getMessage());
            throw $exception;
        }
    }

    public function test_detaching_installment_plan_removes_orphan(): void
    {
        $invoice = $this->createInvoice();
        $plan = new InstallmentPlan();
        $plan->addInstallment(
            position: 0,
            percentage: '50.00',
            amount: new AmountBreakdown(
                net: new Money('500.00'),
                tax: new Money('100.00'),
                gross: new Money('600.00'),
            )
        );
        $plan->addInstallment(
            position: 1,
            percentage: '50.00',
            amount: new AmountBreakdown(
                net: new Money('500.00'),
                tax: new Money('100.00'),
                gross: new Money('600.00'),
            )
        );

        $invoice->attachInstallmentPlan($plan);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $planId = $plan->id?->toRfc4122();
        static::assertNotNull($planId);

        $invoice->detachInstallmentPlan();
        static::assertNull($invoice->installmentPlan);

        $this->entityManager->flush();

        $removed = $this->entityManager->getRepository(InstallmentPlan::class)->find($planId);
        static::assertNull($removed);
    }

    public function test_detaching_recurrence_removes_orphan(): void
    {
        $invoice = $this->createInvoice();
        $recurrence = new InvoiceRecurrence(
            RecurrenceFrequency::MONTHLY,
            interval: 1,
            anchorDate: new \DateTimeImmutable('2025-01-01'),
            endStrategy: RecurrenceEndStrategy::UNTIL_DATE,
            nextRunAt: null,
            endDate: new \DateTimeImmutable('2025-06-01'),
        );

        $invoice->attachRecurrence($recurrence);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $recurrenceId = $recurrence->id?->toRfc4122();
        static::assertNotNull($recurrenceId);

        $invoice->detachRecurrence();
        $this->entityManager->flush();

        static::assertNull($invoice->recurrence);

        $removed = $this->entityManager->getRepository(InvoiceRecurrence::class)->find($recurrenceId);
        static::assertNull($removed);
    }

    private function createInvoice(): Invoice
    {
        return new Invoice(
            title: 'Soft XOR Invoice',
            currency: 'EUR',
            vatRate: new VatRate('20'),
            total: new AmountBreakdown(
                net: new Money('100'),
                tax: new Money('20'),
                gross: new Money('120'),
            ),
            customerSnapshot: ['name' => 'Client'],
            companySnapshot: ['name' => 'Company'],
        );
    }

    private function forceInstallmentPlan(Invoice $invoice, InstallmentPlan $plan): void
    {
        $invoiceProperty = new \ReflectionProperty(Invoice::class, 'installmentPlan');
        $invoiceProperty->setAccessible(true);
        $invoiceProperty->setValue($invoice, $plan);

        $planProperty = new \ReflectionProperty(InstallmentPlan::class, 'invoice');
        $planProperty->setAccessible(true);
        $planProperty->setValue($plan, $invoice);
    }
}
