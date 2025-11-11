<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Invoice\Handler;

use App\Application\Exception\DomainRuleViolationException;
use App\Application\Service\Document\DocumentFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Invoice\Handler\DetachInvoiceInstallmentPlanHandler;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\DetachInvoiceInstallmentPlanTask;
use App\Domain\Contracts\QuoteRepositoryInterface;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use App\Domain\Entity\Document\Quote as QuoteAlias;
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

        return new DetachInvoiceInstallmentPlanHandler(
            new InvoiceRepositoryStub($invoice),
            $this->documentFetcherStub($invoice),
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

    private function documentFetcherStub(Invoice $invoice): DocumentFetcher
    {
        $quoteRepository = new class implements QuoteRepositoryInterface {
            public function save(QuoteAlias $quote): void
            {
            }

            public function remove(QuoteAlias $quote): void
            {
            }

            public function findOneById(Uuid $id): QuoteAlias
            {
                throw new \LogicException('Quote repository not expected in invoice handler tests.');
            }

            public function list(): array
            {
                return [];
            }
        };

        return new DocumentFetcher(new InvoiceRepositoryStub($invoice), $quoteRepository);
    }

    private function workflowManager(WorkflowInterface $invoiceWorkflow): DocumentWorkflowManager
    {
        $quoteWorkflow = static::createStub(WorkflowInterface::class);
        $quoteWorkflow->method('getEnabledTransitions')->willReturn([]);

        return new DocumentWorkflowManager($invoiceWorkflow, $quoteWorkflow);
    }
}
