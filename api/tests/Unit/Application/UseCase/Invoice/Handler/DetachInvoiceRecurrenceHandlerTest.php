<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Invoice\Handler;

use App\Application\Exception\DomainRuleViolationException;
use App\Application\Service\Document\DocumentFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Invoice\Handler\DetachInvoiceRecurrenceHandler;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\DetachInvoiceRecurrenceTask;
use App\Domain\Contracts\QuoteRepositoryInterface;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Entity\Document\Invoice\InvoiceRecurrence;
use App\Domain\Entity\Document\Quote;
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
final class DetachInvoiceRecurrenceHandlerTest extends TestCase
{
    public function test_detach_removes_recurrence(): void
    {
        $invoice = $this->createInvoice();
        $invoice->attachRecurrence($this->createRecurrence());

        $handler = new DetachInvoiceRecurrenceHandler(
            new InvoiceRepositoryStub($invoice),
            $this->documentFetcherStub($invoice),
            new InvoiceOutputMapper(),
            $this->workflowManager($this->createWorkflowStub())
        );

        $output = $handler->handle(new DetachInvoiceRecurrenceTask(Uuid::v7()->toRfc4122()));

        static::assertNull($invoice->recurrence);
        static::assertNull($output->recurrence);
    }

    public function test_detach_without_recurrence_throws(): void
    {
        $invoice = $this->createInvoice();

        $handler = new DetachInvoiceRecurrenceHandler(
            new InvoiceRepositoryStub($invoice),
            $this->documentFetcherStub($invoice),
            new InvoiceOutputMapper(),
            $this->workflowManager($this->createWorkflowStub())
        );

        $this->expectException(DomainRuleViolationException::class);

        $handler->handle(new DetachInvoiceRecurrenceTask(Uuid::v7()->toRfc4122()));
    }

    private function createWorkflowStub(): WorkflowInterface
    {
        $workflow = static::createStub(WorkflowInterface::class);
        $workflow->method('getEnabledTransitions')->willReturn([]);

        return $workflow;
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

    private function documentFetcherStub(Invoice $invoice): DocumentFetcher
    {
        $quoteRepository = new class implements QuoteRepositoryInterface {
            public function save(Quote $quote): void
            {
            }

            public function remove(Quote $quote): void
            {
            }

            public function findOneById(Uuid $id): Quote
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
