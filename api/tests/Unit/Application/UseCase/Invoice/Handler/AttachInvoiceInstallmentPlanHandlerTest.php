<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Invoice\Handler;

use App\Application\Exception\DomainRuleViolationException;
use App\Application\Service\Document\InstallmentAllocator;
use App\Application\Service\EntityFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Invoice\Handler\AttachInvoiceInstallmentPlanHandler;
use App\Application\UseCase\Invoice\Input\InvoiceInstallmentPlanInput;
use App\Application\UseCase\Invoice\Input\Mapper\InvoiceInstallmentPlanMapper;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\AttachInvoiceInstallmentPlanTask;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use App\Domain\Entity\Document\Invoice\InvoiceRecurrence;
use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VatRate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @testType solitary-unit
 */
final class AttachInvoiceInstallmentPlanHandlerTest extends TestCase
{
    public function test_handle_attaches_plan(): void
    {
        $invoice = $this->createInvoice();
        $handler = $this->createHandler($invoice);

        $command = new AttachInvoiceInstallmentPlanTask(
            invoiceId: Uuid::v7()->toRfc4122(),
            input: $this->planInput(),
        );

        $output = $handler->handle($command);

        static::assertNotNull($invoice->installmentPlan);
        static::assertCount(2, $invoice->installmentPlan->installments());
        static::assertSame('60.00', $invoice->installmentPlan->installments()[0]->amount->gross->value ?? null);
        static::assertSame('2025-01-01', $output->installmentPlan->installments[0]->dueDate);
        static::assertSame('60.00', $invoice->installmentPlan->installments()[1]->amount->gross->value ?? null);
        static::assertSame('2025-02-01', $output->installmentPlan->installments[1]->dueDate);
    }

    public function test_handle_rejects_when_recurrence_exists(): void
    {
        $invoice = $this->createInvoice();
        $invoice->attachRecurrence($this->createRecurrence());

        $handler = $this->createHandler($invoice);

        $this->expectException(DomainRuleViolationException::class);

        $handler->handle(
            new AttachInvoiceInstallmentPlanTask(
                invoiceId: Uuid::v7()->toRfc4122(),
                input: $this->planInput(),
            )
        );
    }

    public function test_handle_replaces_existing_plan_when_flagged(): void
    {
        $invoice = $this->createInvoice();
        $invoice->attachInstallmentPlan(new InstallmentPlan());

        $handler = $this->createHandler($invoice);

        $output = $handler->handle(
            new AttachInvoiceInstallmentPlanTask(
                invoiceId: Uuid::v7()->toRfc4122(),
                input: $this->planInput(),
                replaceExisting: true,
            )
        );

        static::assertNotNull($invoice->installmentPlan);
        static::assertCount(2, $invoice->installmentPlan->installments());
        static::assertSame('2025-01-01', $output->installmentPlan->installments[0]->dueDate);
    }

    private function createHandler(Invoice $invoice): AttachInvoiceInstallmentPlanHandler
    {
        $workflow = $this->createWorkflowStub();

        $invoiceRepository = new InvoiceRepositoryStub($invoice);

        return new AttachInvoiceInstallmentPlanHandler(
            $invoiceRepository,
            $this->entityFetcherStub($invoiceRepository),
            new InvoiceOutputMapper(),
            new InvoiceInstallmentPlanMapper(new InstallmentAllocator()),
            $this->workflowManager($workflow)
        );
    }

    private function createWorkflowStub(): WorkflowInterface
    {
        $workflow = static::createStub(WorkflowInterface::class);
        $workflow->method('getEnabledTransitions')->willReturn([]);

        return $workflow;
    }

    private function planInput(): InvoiceInstallmentPlanInput
    {
        return new InvoiceInstallmentPlanInput([
            ['percentage' => 50, 'dueDate' => '2025-01-01'],
            ['percentage' => 50, 'dueDate' => '2025-02-01'],
        ]);
    }

    private function createInvoice(): Invoice
    {
        return new Invoice(
            title: 'Sample invoice',
            currency: 'EUR',
            vatRate: new VatRate('20'),
            total: new AmountBreakdown(
                net: new Money('100'),
                tax: new Money('20'),
                gross: new Money('120'),
            ),
            customerSnapshot: ['name' => 'Client'],
            companySnapshot: ['name' => 'My Company']
        );
    }

    private function createRecurrence(): InvoiceRecurrence
    {
        return new InvoiceRecurrence(
            frequency: RecurrenceFrequency::MONTHLY,
            interval: 1,
            anchorDate: new \DateTimeImmutable('2025-01-01'),
            endStrategy: RecurrenceEndStrategy::NEVER,
        );
    }

    private function entityFetcherStub(InvoiceRepositoryInterface $invoiceRepository): EntityFetcher
    {
        $fetcher = new EntityFetcher();
        $fetcher->setInvoiceRepository($invoiceRepository);

        return $fetcher;
    }

    private function workflowManager(WorkflowInterface $invoiceWorkflow): DocumentWorkflowManager
    {
        $workflowManager = new DocumentWorkflowManager();
        $workflowManager->setInvoiceWorkflow($invoiceWorkflow);

        return $workflowManager;
    }
}
