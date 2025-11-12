<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Invoice\Handler;

use App\Application\Exception\DomainRuleViolationException;
use App\Application\Service\Document\InstallmentAllocator;
use App\Application\UseCase\Invoice\Handler\AttachInvoiceInstallmentPlanHandler;
use App\Application\UseCase\Invoice\Input\InvoiceInstallmentPlanInput;
use App\Application\UseCase\Invoice\Input\Mapper\InvoiceInstallmentPlanMapper;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\AttachInvoiceInstallmentPlanTask;
use App\Domain\Entity\Document\Invoice;
use App\Tests\Factory\Document\InvoiceFactory;
use App\Tests\Factory\ValueObject\AmountBreakdownFactory;
use App\Tests\Factory\ValueObject\MoneyFactory;
use App\Tests\Unit\Application\UseCase\Stub\EntityFetcherStub;
use App\Tests\Unit\Application\UseCase\Stub\InvoiceRepositoryStub;
use App\Tests\Unit\Application\UseCase\Stub\WorkflowManagerStub;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @testType sociable-unit
 */
final class AttachInvoiceInstallmentPlanHandlerTest extends TestCase
{
    use Factories;

    private InvoiceInstallmentPlanInput $planInput;

    private AttachInvoiceInstallmentPlanTask $task;

    protected function setUp(): void
    {
        $this->planInput = new InvoiceInstallmentPlanInput([
            ['percentage' => 50, 'dueDate' => '2025-01-01'],
            ['percentage' => 50, 'dueDate' => '2025-02-01'],
        ]);

        $this->task = new AttachInvoiceInstallmentPlanTask(
            invoiceId: Uuid::v7()->toRfc4122(),
            input: $this->planInput,
        );
    }

    public function test_handle_attaches_plan(): void
    {
        $invoice = InvoiceFactory::build([
            'total' => AmountBreakdownFactory::new([
                'gross' => MoneyFactory::new(['value' => '200']),
                'net' => MoneyFactory::new(['value' => '150']),
                'tax' => MoneyFactory::new(['value' => '50']),
            ]),
        ])->create();

        $output = $this->createHandler($invoice)->handle($this->task);

        static::assertNotNull($invoice->installmentPlan);
        static::assertCount(2, $invoice->installmentPlan->installments());
        static::assertSame('100.00', $invoice->installmentPlan->installments()[0]->amount->gross->value ?? null);
        static::assertSame('2025-01-01', $output->installmentPlan->installments[0]->dueDate ?? null);
        static::assertSame('100.00', $invoice->installmentPlan->installments()[1]->amount->gross->value ?? null);
        static::assertSame('2025-02-01', $output->installmentPlan->installments[1]->dueDate ?? null);
    }

    public function test_handle_rejects_when_recurrence_exists(): void
    {
        $invoice = InvoiceFactory::build()->withRecurrence()->create();

        $this->expectException(DomainRuleViolationException::class);

        $this->createHandler($invoice)->handle($this->task);
    }

    #[DataProviderExternal(AttachInvoiceRecurrenceHandlerTest::class, 'generatedFromSeedProvider')]
    public function test_handle_rejects_when_invoice_is_generated_from_seed(Invoice $invoice): void
    {
        $this->expectException(DomainRuleViolationException::class);

        $this->createHandler($invoice)->handle($this->task);
    }

    public function test_handle_replaces_existing_plan_when_flagged(): void
    {
        $invoice = InvoiceFactory::build()->withInstallmentPlan()->create();

        $task = new AttachInvoiceInstallmentPlanTask(
            invoiceId: Uuid::v7()->toRfc4122(),
            input: $this->planInput,
            replaceExisting: true,
        );

        $output = $this->createHandler($invoice)->handle($task);

        static::assertNotNull($invoice->installmentPlan);
        static::assertCount(2, $invoice->installmentPlan->installments());
        static::assertSame('2025-01-01', $output->installmentPlan->installments[0]->dueDate);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function createHandler(Invoice $invoice): AttachInvoiceInstallmentPlanHandler
    {
        $invoiceRepository = new InvoiceRepositoryStub($invoice);

        return new AttachInvoiceInstallmentPlanHandler(
            invoiceRepository: $invoiceRepository,
            entityFetcher: EntityFetcherStub::create(invoiceRepository: $invoiceRepository),
            outputMapper: new InvoiceOutputMapper(),
            planMapper: new InvoiceInstallmentPlanMapper(new InstallmentAllocator()),
            workflowManager: WorkflowManagerStub::create()
        );
    }
}
