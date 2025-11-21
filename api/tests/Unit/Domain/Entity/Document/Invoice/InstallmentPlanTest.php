<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Document\Invoice;

use App\Domain\Entity\Document\Invoice\Installment;
use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use App\Domain\Exception\DocumentRuleViolationException;
use App\Domain\Payload\Invoice\Installment\InstallmentPayload;
use App\Domain\Payload\Invoice\Installment\InstallmentPlanPayload;
use App\Domain\ValueObject\AmountBreakdown;
use App\Tests\TestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @testType solitary-unit
 */
final class InstallmentPlanTest extends TestCase
{
    use Factories;

    public function test_from_payload_allocates_amounts(): void
    {
        $plan = static::createInstallmentPlan([
            ['percentage' => '40.00'],
            ['percentage' => '60.00'],
        ]);

        $installments = $plan->installments;

        static::assertCount(2, $installments);
        static::assertSame('480.00', $installments[0]->amount->gross->value);
        static::assertSame('720.00', $installments[1]->amount->gross->value);
    }

    public function test_residual_amount_is_applied_to_last_installment(): void
    {
        $plan = static::createInstallmentPlan([
            ['percentage' => '33.33'],
            ['percentage' => '33.33'],
            ['percentage' => '33.34'],
        ]);

        $grossSum = $plan->installments->reduce(
            fn (string $carry, Installment $installment) => bcadd($carry, $installment->amount->gross->value, 2),
            '0.00'
        );

        static::assertSame('1200.00', $grossSum);
    }

    public function test_apply_payload_updates_existing_installments(): void
    {
        $plan = static::createInstallmentPlan([['percentage' => '50.00'], ['percentage' => '50.00']]);
        TestHelper::setProperty($plan, 'invoice', InvoiceTest::createInvoice());

        $id1 = Uuid::fromString(TestHelper::generateUuid(1));
        $id2 = Uuid::fromString(TestHelper::generateUuid(2));

        $installments = $plan->installments->getValues();
        TestHelper::setProperty($installments[0], 'id', $id1);
        TestHelper::setProperty($installments[1], 'id', $id2);

        $payload = new InstallmentPlanPayload([
            new InstallmentPayload($id1, '40.00', null),
            new InstallmentPayload($id2, '60.00', null),
        ]);

        $plan->applyPayload($payload);

        $installments = $plan->installments->getValues();
        static::assertCount(2, $installments);
        static::assertSame('40.00', $installments[0]->percentage);
        static::assertSame('60.00', $installments[1]->percentage);
    }

    public function test_apply_payload_adds_and_removes_installments(): void
    {
        $plan = static::createInstallmentPlan([['percentage' => '50.00'], ['percentage' => '50.00']]);
        TestHelper::setProperty($plan, 'invoice', InvoiceTest::createInvoice());

        $installment = $plan->installments->first();
        static::assertNotFalse($installment);

        $id1 = Uuid::fromString(TestHelper::generateUuid(1));
        TestHelper::setProperty($installment, 'id', $id1);

        $payload = new InstallmentPlanPayload([
            new InstallmentPayload($id1, '70.00', null),
            new InstallmentPayload(null, '30.00', null),
        ]);

        $plan->applyPayload($payload);

        $installments = $plan->installments->getValues();
        static::assertCount(2, $installments);
        static::assertSame('70.00', $installments[0]->percentage);
        static::assertSame('30.00', $installments[1]->percentage);
    }

    public function test_apply_payload_mutates_other_installments_than_generated_installments(): void
    {
        $total = AmountBreakdown::fromValues('1000.00', '200.00', '1200.00');
        $invoice = InvoiceTest::createInvoice();
        TestHelper::setProperty($invoice, 'total', $total);

        $plan = static::createInstallmentPlan([['percentage' => '50.00'], ['percentage' => '50.00']]);
        TestHelper::setProperty($plan, 'invoice', $invoice);

        $id1 = Uuid::fromString(TestHelper::generateUuid(1));
        $id2 = Uuid::fromString(TestHelper::generateUuid(2));

        $installments = $plan->installments->getValues();
        TestHelper::setProperty($installments[0], 'id', $id1);
        TestHelper::setProperty($installments[0], 'generatedInvoiceId', Uuid::v7());
        TestHelper::setProperty($installments[1], 'id', $id2);

        $dueDate = new \DateTimeImmutable('tomorrow');

        $plan->applyPayload(new InstallmentPlanPayload([
            new InstallmentPayload($id1, '50.00', null),
            new InstallmentPayload($id2, '50.00', $dueDate),
        ]));

        $installments = $plan->installments->getValues();
        static::assertCount(2, $installments);
        static::assertSame('50.00', $installments[0]->percentage);
        static::assertSame('50.00', $installments[1]->percentage);
        static::assertSame($dueDate, $installments[1]->dueDate);
    }

    public function test_apply_payload_throws_when_generated_installment_mutated(): void
    {
        $total = AmountBreakdown::fromValues('1000.00', '200.00', '1200.00');
        $invoice = InvoiceTest::createInvoice();
        TestHelper::setProperty($invoice, 'total', $total);

        $plan = static::createInstallmentPlan([['percentage' => '100.00']]);
        TestHelper::setProperty($plan, 'invoice', $invoice);

        $installment = $plan->installments->first();
        static::assertNotFalse($installment);

        $id1 = Uuid::fromString(TestHelper::generateUuid(1));
        TestHelper::setProperty($installment, 'id', $id1);
        TestHelper::setProperty($installment, 'generatedInvoiceId', Uuid::v7());

        $this->expectException(DocumentRuleViolationException::class);

        $plan->applyPayload(new InstallmentPlanPayload([
            new InstallmentPayload($id1, '100.00', new \DateTimeImmutable('tomorrow')),
        ]));
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function createInstallmentPlan(array $installments): InstallmentPlan
    {
        $payload = new InstallmentPlanPayload(
            array_map(
                fn (array $data) => new InstallmentPayload(
                    id: null,
                    percentage: $data['percentage'],
                    dueDate: $data['dueDate'] ?? null,
                ),
                array_values($installments)
            )
        );

        $invoiceTotal = AmountBreakdown::fromValues('1000.00', '200.00', '1200.00');

        return InstallmentPlan::fromPayload($payload, $invoiceTotal);
    }
}
