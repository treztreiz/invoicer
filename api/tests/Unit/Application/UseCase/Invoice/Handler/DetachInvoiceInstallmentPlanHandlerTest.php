<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Invoice\Handler;

use App\Application\Exception\DomainRuleViolationException;
use App\Application\Service\EntityFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Invoice\Handler\DetachInvoiceInstallmentPlanHandler;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\DetachInvoiceInstallmentPlanTask;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VatRate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @testType solitary-unit
 */
final class DetachInvoiceInstallmentPlanHandlerTest extends TestCase
{
    public function test_detach_removes_plan(): void
    {
        $invoice = $this->createInvoice();
        $invoice->attachInstallmentPlan(new InstallmentPlan());

        $handler = $this->createHandler($invoice);

        $output = $handler->handle(new DetachInvoiceInstallmentPlanTask(Uuid::v7()->toRfc4122()));

        static::assertNull($invoice->installmentPlan);
        static::assertNull($output->installmentPlan);
    }

    public function test_detach_without_plan_throws(): void
    {
        $handler = $this->createHandler($this->createInvoice());

        $this->expectException(DomainRuleViolationException::class);

        $handler->handle(new DetachInvoiceInstallmentPlanTask(Uuid::v7()->toRfc4122()));
    }

    private function createHandler(Invoice $invoice): DetachInvoiceInstallmentPlanHandler
    {
        $workflow = static::createStub(WorkflowInterface::class);
        $workflow->method('getEnabledTransitions')->willReturn([]);

        $invoiceRepository = new InvoiceRepositoryStub($invoice);

        return new DetachInvoiceInstallmentPlanHandler(
            $invoiceRepository,
            $this->entityFetcherStub($invoiceRepository),
            new InvoiceOutputMapper(),
            $this->workflowManager($workflow)
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
