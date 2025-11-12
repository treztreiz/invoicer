<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Invoice\Handler;

use App\Application\Exception\DomainRuleViolationException;
use App\Application\Service\Document\DocumentLineFactory;
use App\Application\Service\Document\DocumentLinePayloadFactory;
use App\Application\Service\Document\DocumentSnapshotFactory;
use App\Application\UseCase\Invoice\Handler\UpdateInvoiceHandler;
use App\Application\UseCase\Invoice\Input\InvoiceInput;
use App\Application\UseCase\Invoice\Input\Mapper\InvoicePayloadMapper;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\UpdateInvoiceTask;
use App\Domain\DTO\DocumentLinePayload;
use App\Domain\DTO\InvoicePayload;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Enum\RateUnit;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;
use App\Domain\ValueObject\VatRate;
use App\Tests\Unit\Application\UseCase\Common\InvoiceRepositoryStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * @testType solitary-unit
 */
final class UpdateInvoiceHandlerTest extends TestCase
{
    public function test_handle_updates_invoice(): void
    {
        $invoice = $this->createInvoice();

        $lineFactory = new DocumentLineFactory();
        $linePayloadFactory = new DocumentLinePayloadFactory($lineFactory);
        $invoiceRepository = new InvoiceRepositoryStub($invoice);
        $handler = new UpdateInvoiceHandler(
            invoiceRepository: $invoiceRepository,
            payloadMapper: new InvoicePayloadMapper(new DocumentSnapshotFactory(), $linePayloadFactory),
            outputMapper: new InvoiceOutputMapper(),
            entityFetcher: $this->entityFetcherForInvoice($invoiceRepository),
            workflowManager: $this->workflowManagerStub()
        );

        $input = $this->invoiceInput();
        $command = new UpdateInvoiceTask(Uuid::v7()->toRfc4122(), $input);

        $output = $handler->handle($command);

        static::assertSame('Updated invoice', $output->title);
        static::assertSame('USD', $output->currency);
        static::assertSame('Updated subtitle', $output->subtitle);
        static::assertCount(1, $output->lines);
    }

    public function test_handle_rejects_non_draft(): void
    {
        $invoice = $this->createInvoice();
        $invoice->issue(new \DateTimeImmutable('2025-01-01T00:00:00Z'), new \DateTimeImmutable('2025-01-10'));

        $lineFactory = new DocumentLineFactory();
        $linePayloadFactory = new DocumentLinePayloadFactory($lineFactory);
        $invoiceRepository = new InvoiceRepositoryStub($invoice);
        $handler = new UpdateInvoiceHandler(
            invoiceRepository: $invoiceRepository,
            payloadMapper: new InvoicePayloadMapper(new DocumentSnapshotFactory(), $linePayloadFactory),
            outputMapper: new InvoiceOutputMapper(),
            entityFetcher: $this->entityFetcherForInvoice($invoiceRepository),
            workflowManager: $this->workflowManagerStub()
        );

        $this->expectException(DomainRuleViolationException::class);

        $handler->handle(new UpdateInvoiceTask(Uuid::v7()->toRfc4122(), $this->invoiceInput()));
    }

    private function invoiceInput(): InvoiceInput
    {
        $input = new InvoiceInput(
            title: 'Updated invoice',
            currency: 'USD',
            vatRate: 15,
            lines: [
                [
                    'description' => 'Consulting',
                    'quantity' => 3,
                    'rateUnit' => 'DAILY',
                    'rate' => 700,
                ],
            ],
            customerId: Uuid::v7()->toRfc4122(),
            dueDate: '2025-02-01',
            subtitle: 'Updated subtitle',
        );

        $input->userId = Uuid::v7()->toRfc4122();

        return $input;
    }

    private function createInvoice(): Invoice
    {
        return Invoice::fromPayload(
            new InvoicePayload(
                title: 'Initial invoice',
                subtitle: 'Initial subtitle',
                currency: 'EUR',
                vatRate: new VatRate('20.00'),
                total: new AmountBreakdown(
                    net: new Money('100.00'),
                    tax: new Money('20.00'),
                    gross: new Money('120.00'),
                ),
                lines: [
                    new DocumentLinePayload(
                        description: 'Development',
                        quantity: new Quantity('1.000'),
                        rateUnit: RateUnit::HOURLY,
                        rate: new Money('100.00'),
                        amount: new AmountBreakdown(
                            net: new Money('100.00'),
                            tax: new Money('20.00'),
                            gross: new Money('120.00'),
                        ),
                        position: 0,
                    ),
                ],
                customerSnapshot: ['name' => 'Customer'],
                companySnapshot: ['name' => 'Company'],
                dueDate: new \DateTimeImmutable('2025-01-10'),
            )
        );
    }
}
