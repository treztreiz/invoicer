<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Invoice\Handler;

use App\Application\Exception\DomainRuleViolationException;
use App\Application\UseCase\Invoice\Handler\DetachInvoiceRecurrenceHandler;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\DetachInvoiceRecurrenceTask;
use App\Domain\Entity\Document\Invoice;
use App\Tests\Factory\Document\InvoiceFactory;
use App\Tests\Unit\Application\UseCase\Common\EntityFetcherStub;
use App\Tests\Unit\Application\UseCase\Common\InvoiceRepositoryStub;
use App\Tests\Unit\Application\UseCase\Common\WorkflowManagerStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @testType solitary-unit
 */
final class DetachInvoiceRecurrenceHandlerTest extends TestCase
{
    use Factories;

    private DetachInvoiceRecurrenceTask $task;

    protected function setUp(): void
    {
        $this->task = new DetachInvoiceRecurrenceTask(invoiceId: Uuid::v7()->toRfc4122());
    }

    public function test_detach_removes_recurrence(): void
    {
        $invoice = InvoiceFactory::build()->withRecurrence()->create();

        $output = $this->createHandler($invoice)->handle($this->task);

        static::assertNull($invoice->recurrence);
        static::assertNull($output->recurrence);
    }

    public function test_detach_without_recurrence_throws(): void
    {
        $invoice = InvoiceFactory::build()->create();

        $this->expectException(DomainRuleViolationException::class);

        $this->createHandler($invoice)->handle($this->task);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function createHandler(Invoice $invoice): DetachInvoiceRecurrenceHandler
    {
        $invoiceRepository = new InvoiceRepositoryStub($invoice);

        return new DetachInvoiceRecurrenceHandler(
            invoiceRepository: $invoiceRepository,
            entityFetcher: EntityFetcherStub::create(invoiceRepository: $invoiceRepository),
            outputMapper: new InvoiceOutputMapper(),
            workflowManager: WorkflowManagerStub::create()
        );
    }
}
