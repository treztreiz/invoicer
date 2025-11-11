<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Invoice\Handler;

use App\Application\Exception\DomainRuleViolationException;
use App\Application\Service\EntityFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Invoice\Handler\AttachInvoiceRecurrenceHandler;
use App\Application\UseCase\Invoice\Input\InvoiceRecurrenceInput;
use App\Application\UseCase\Invoice\Input\Mapper\InvoiceRecurrenceMapper;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\AttachInvoiceRecurrenceTask;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Entity\Document\Invoice\InstallmentPlan;
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
final class AttachInvoiceRecurrenceHandlerTest extends TestCase
{
    public function test_handle_attaches_recurrence_and_returns_output(): void
    {
        $invoice = $this->createInvoice();
        $repository = new InvoiceRepositoryStub($invoice);
        $workflow = $this->createWorkflowStub();
        $handler = new AttachInvoiceRecurrenceHandler(
            $repository,
            $this->entityFetcherStub($repository),
            new InvoiceOutputMapper(),
            new InvoiceRecurrenceMapper(),
            $this->workflowManager($workflow)
        );

        $input = $this->createInput();

        $command = new AttachInvoiceRecurrenceTask(Uuid::v7()->toRfc4122(), $input);

        $output = $handler->handle($command);

        static::assertNotNull($invoice->recurrence);
        static::assertSame('MONTHLY', $output->recurrence->frequency);
        static::assertSame('2025-12-31', $output->recurrence->endDate);
    }

    public function test_handle_rejects_when_installment_plan_exists(): void
    {
        $invoice = $this->createInvoice();
        $invoice->attachInstallmentPlan(new InstallmentPlan());

        $handler = new AttachInvoiceRecurrenceHandler(
            new InvoiceRepositoryStub($invoice),
            $this->entityFetcherStub(new InvoiceRepositoryStub($invoice)),
            new InvoiceOutputMapper(),
            new InvoiceRecurrenceMapper(),
            $this->workflowManager($this->createWorkflowStub())
        );

        $command = new AttachInvoiceRecurrenceTask(Uuid::v7()->toRfc4122(), $this->createInput());

        $this->expectException(DomainRuleViolationException::class);

        $handler->handle($command);
    }

    public function test_handle_rejects_when_invoice_is_generated_from_seed(): void
    {
        $invoice = $this->createInvoice();
        $invoice->markGeneratedFromRecurrence(Uuid::v7());

        $handler = new AttachInvoiceRecurrenceHandler(
            new InvoiceRepositoryStub($invoice),
            $this->entityFetcherStub(new InvoiceRepositoryStub($invoice)),
            new InvoiceOutputMapper(),
            new InvoiceRecurrenceMapper(),
            $this->workflowManager($this->createWorkflowStub())
        );

        $command = new AttachInvoiceRecurrenceTask(Uuid::v7()->toRfc4122(), $this->createInput());

        $this->expectException(DomainRuleViolationException::class);

        $handler->handle($command);
    }

    private function createWorkflowStub(): WorkflowInterface
    {
        $workflow = static::createStub(WorkflowInterface::class);
        $workflow->method('getEnabledTransitions')->willReturn([]);

        return $workflow;
    }

    private function createInput(array $override = []): InvoiceRecurrenceInput
    {
        return new InvoiceRecurrenceInput(
            frequency: $override['frequency'] ?? RecurrenceFrequency::MONTHLY->value,
            interval: $override['interval'] ?? 1,
            anchorDate: $override['anchorDate'] ?? '2025-01-01',
            endStrategy: $override['endStrategy'] ?? RecurrenceEndStrategy::UNTIL_DATE->value,
            endDate: $override['endDate'] ?? '2025-12-31',
            occurrenceCount: $override['occurrenceCount'] ?? null,
        );
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
