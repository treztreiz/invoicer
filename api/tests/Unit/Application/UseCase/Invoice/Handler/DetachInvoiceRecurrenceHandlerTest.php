<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Invoice\Handler;

use App\Application\Dto\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\Exception\DomainRuleViolationException;
use App\Application\UseCase\Invoice\Recurrence\DetachInvoiceRecurrenceTask;
use App\Application\UseCase\Invoice\Recurrence\DetachRecurrenceUseCase;
use App\Domain\Entity\Document\Invoice\Invoice;
use App\Tests\Factory\Document\Invoice\InvoiceFactory;
use App\Tests\Unit\Application\Stub\EntityFetcherStub;
use App\Tests\Unit\Application\Stub\InvoiceRepositoryStub;
use App\Tests\Unit\Application\Stub\WorkflowManagerStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @testType sociable-unit
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

    private function createHandler(Invoice $invoice): DetachRecurrenceUseCase
    {
        $invoiceRepository = new InvoiceRepositoryStub($invoice);

        return new DetachRecurrenceUseCase(
            invoiceRepository: $invoiceRepository,
            entityFetcher: EntityFetcherStub::create(invoiceRepository: $invoiceRepository),
            outputMapper: new InvoiceOutputMapper(),
            workflowManager: WorkflowManagerStub::create()
        );
    }
}
