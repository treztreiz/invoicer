<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Invoice\Handler;

use App\Application\Exception\DomainRuleViolationException;
use App\Application\UseCase\Invoice\Handler\AttachInvoiceRecurrenceHandler;
use App\Application\UseCase\Invoice\Input\InvoiceRecurrenceInput;
use App\Application\UseCase\Invoice\Input\Mapper\InvoiceRecurrenceMapper;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\AttachInvoiceRecurrenceTask;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;
use App\Tests\Factory\Document\InvoiceFactory;
use App\Tests\Unit\Application\UseCase\Common\EntityFetcherStub;
use App\Tests\Unit\Application\UseCase\Common\InvoiceRepositoryStub;
use App\Tests\Unit\Application\UseCase\Common\WorkflowManagerStub;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @testType sociable-unit
 */
final class AttachInvoiceRecurrenceHandlerTest extends TestCase
{
    use Factories;

    private AttachInvoiceRecurrenceTask $task;

    protected function setUp(): void
    {
        $recurrenceInput = new InvoiceRecurrenceInput(
            frequency: RecurrenceFrequency::MONTHLY->value,
            interval: 1,
            anchorDate: '2025-01-01',
            endStrategy: RecurrenceEndStrategy::UNTIL_DATE->value,
            endDate: '2025-12-31',
            occurrenceCount: null,
        );

        $this->task = new AttachInvoiceRecurrenceTask(
            invoiceId: Uuid::v7()->toRfc4122(),
            input: $recurrenceInput
        );
    }

    public function test_handle_attaches_recurrence_and_returns_output(): void
    {
        $invoice = InvoiceFactory::build()->create();

        $output = $this->createHandler($invoice)->handle($this->task);

        static::assertNotNull($invoice->recurrence);
        static::assertSame('MONTHLY', $output->recurrence->frequency);
        static::assertSame('2025-12-31', $output->recurrence->endDate);
    }

    public function test_handle_rejects_when_installment_plan_exists(): void
    {
        $invoice = InvoiceFactory::build()->withInstallmentPlan()->create();

        $this->expectException(DomainRuleViolationException::class);

        $this->createHandler($invoice)->handle($this->task);
    }

    #[DataProvider('generatedFromSeedProvider')]
    public function test_handle_rejects_when_invoice_is_generated_from_seed(Invoice $invoice): void
    {
        $this->expectException(DomainRuleViolationException::class);

        $this->createHandler($invoice)->handle($this->task);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function createHandler(Invoice $invoice): AttachInvoiceRecurrenceHandler
    {
        $invoiceRepository = new InvoiceRepositoryStub($invoice);

        return new AttachInvoiceRecurrenceHandler(
            invoiceRepository: $invoiceRepository,
            entityFetcher: EntityFetcherStub::create(invoiceRepository: $invoiceRepository),
            outputMapper: new InvoiceOutputMapper(),
            recurrenceMapper: new InvoiceRecurrenceMapper(),
            workflowManager: WorkflowManagerStub::create()
        );
    }

    public static function generatedFromSeedProvider(): iterable
    {
        yield 'Generated from recurrence' => [
            InvoiceFactory::build()->generatedFromRecurrence()->create(),
        ];

        yield 'Generated from installment' => [
            InvoiceFactory::build()->generatedFromInstallment()->create(),
        ];
    }
}
