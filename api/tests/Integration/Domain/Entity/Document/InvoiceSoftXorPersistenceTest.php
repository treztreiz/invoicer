<?php

/** @noinspection PhpRedundantCatchClauseInspection */

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Entity\Document;

use App\Tests\Factory\Document\Invoice\InstallmentFactory;
use App\Tests\Factory\Document\Invoice\InstallmentPlanFactory;
use App\Tests\Factory\Document\Invoice\InvoiceFactory;
use App\Tests\Factory\Document\Invoice\RecurrenceFactory;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function Zenstruck\Foundry\Persistence\flush_after;
use function Zenstruck\Foundry\Persistence\save;

/**
 * @testType integration
 */
final class InvoiceSoftXorPersistenceTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function test_invoice_with_only_installment_plan_persists(): void
    {
        $invoice = InvoiceFactory::createOne([
            'installmentPlan' => InstallmentPlanFactory::new(),
        ]);

        static::assertNotNull($invoice->id);
    }

    public function test_invoice_with_only_recurrence_persists(): void
    {
        $invoice = InvoiceFactory::createOne([
            'recurrence' => RecurrenceFactory::new(),
        ]);

        static::assertNotNull($invoice->id);
    }

    public function test_invoice_with_recurrence_and_installments_violates_soft_xor(): void
    {
        $this->expectException(DriverException::class);

        try {
            InvoiceFactory::createOne([
                'recurrence' => RecurrenceFactory::new(),
                'installmentPlan' => InstallmentPlanFactory::new(),
            ]);
        } catch (DriverException $exception) {
            static::assertSame('23514', $exception->getSQLState());
            static::assertStringContainsString('CHK_INVOICE_SCHEDULE_XOR', $exception->getMessage());
            throw $exception;
        }
    }

    public function test_detaching_installment_plan_removes_orphan(): void
    {
        $invoice = flush_after(function () {
            $invoice = InvoiceFactory::createOne();
            $installmentPlan = InstallmentPlanFactory::createOne(['invoice' => $invoice]);
            InstallmentFactory::createOne(['installmentPlan' => $installmentPlan]);

            return $invoice;
        });

        static::assertNotNull($invoice->installmentPlan);
        InstallmentPlanFactory::assert()->count(1);
        InstallmentFactory::assert()->count(1);

        $invoice->detachInstallmentPlan();
        save($invoice);

        static::assertNull($invoice->installmentPlan);
        InstallmentPlanFactory::assert()->empty();
        InstallmentFactory::assert()->empty();
    }

    public function test_detaching_recurrence_removes_orphan(): void
    {
        $invoice = flush_after(function () {
            $invoice = InvoiceFactory::createOne();
            RecurrenceFactory::createOne(['invoice' => $invoice]);

            return $invoice;
        });

        static::assertNotNull($invoice->recurrence);
        RecurrenceFactory::assert()->count(1);

        $invoice->detachRecurrence();
        save($invoice);

        static::assertNull($invoice->recurrence);
        RecurrenceFactory::assert()->empty();
    }
}
