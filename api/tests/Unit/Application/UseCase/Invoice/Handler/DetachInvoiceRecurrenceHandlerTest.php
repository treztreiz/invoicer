<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Invoice\Handler;

use App\Application\UseCase\Invoice\Command\DetachInvoiceRecurrenceCommand;
use App\Application\UseCase\Invoice\Handler\DetachInvoiceRecurrenceHandler;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Domain\Entity\Document\Invoice;
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
final class DetachInvoiceRecurrenceHandlerTest extends TestCase
{
    public function test_detach_removes_recurrence(): void
    {
        $invoice = $this->createInvoice();
        $invoice->attachRecurrence($this->createRecurrence());

        $handler = new DetachInvoiceRecurrenceHandler(
            new InvoiceRepositoryStub($invoice),
            new InvoiceOutputMapper(),
            $this->createWorkflowStub(),
        );

        $output = $handler->handle(new DetachInvoiceRecurrenceCommand(Uuid::v7()->toRfc4122()));

        static::assertNull($invoice->recurrence);
        static::assertNull($output->recurrence);
    }

    public function test_detach_without_recurrence_throws(): void
    {
        $invoice = $this->createInvoice();

        $handler = new DetachInvoiceRecurrenceHandler(
            new InvoiceRepositoryStub($invoice),
            new InvoiceOutputMapper(),
            $this->createWorkflowStub(),
        );

        $this->expectException(\Symfony\Component\HttpKernel\Exception\BadRequestHttpException::class);

        $handler->handle(new DetachInvoiceRecurrenceCommand(Uuid::v7()->toRfc4122()));
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
}
