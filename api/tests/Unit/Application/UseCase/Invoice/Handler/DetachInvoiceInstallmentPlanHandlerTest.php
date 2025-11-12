<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Invoice\Handler;

use App\Application\Exception\DomainRuleViolationException;
use App\Application\UseCase\Invoice\Handler\DetachInvoiceInstallmentPlanHandler;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\DetachInvoiceInstallmentPlanTask;
use App\Domain\Entity\Document\Invoice;
use App\Tests\Factory\Document\InvoiceFactory;
use App\Tests\Unit\Application\UseCase\Stub\EntityFetcherStub;
use App\Tests\Unit\Application\UseCase\Stub\InvoiceRepositoryStub;
use App\Tests\Unit\Application\UseCase\Stub\WorkflowManagerStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @testType sociable-unit
 */
final class DetachInvoiceInstallmentPlanHandlerTest extends TestCase
{
    use Factories;

    private DetachInvoiceInstallmentPlanTask $task;

    protected function setUp(): void
    {
        $this->task = new DetachInvoiceInstallmentPlanTask(invoiceId: Uuid::v7()->toRfc4122());
    }

    public function test_detach_removes_plan(): void
    {
        $invoice = InvoiceFactory::build()->withInstallmentPlan()->create();

        $output = $this->createHandler($invoice)->handle($this->task);

        static::assertNull($invoice->installmentPlan);
        static::assertNull($output->installmentPlan);
    }

    public function test_detach_without_plan_throws(): void
    {
        $invoice = InvoiceFactory::build()->create();

        $this->expectException(DomainRuleViolationException::class);

        $this->createHandler($invoice)->handle($this->task);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function createHandler(Invoice $invoice): DetachInvoiceInstallmentPlanHandler
    {
        $invoiceRepository = new InvoiceRepositoryStub($invoice);

        return new DetachInvoiceInstallmentPlanHandler(
            invoiceRepository: $invoiceRepository,
            entityFetcher: EntityFetcherStub::create(invoiceRepository: $invoiceRepository),
            outputMapper: new InvoiceOutputMapper(),
            workflowManager: WorkflowManagerStub::create()
        );
    }
}
