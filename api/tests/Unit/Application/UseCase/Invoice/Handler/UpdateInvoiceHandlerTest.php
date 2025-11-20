<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Invoice\Handler;

use App\Application\Dto\Invoice\Input\InvoiceInput;
use App\Application\Dto\Invoice\Input\Mapper\InvoicePayloadMapper;
use App\Application\Dto\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\Exception\DomainRuleViolationException;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Service\Document\DocumentLineFactory;
use App\Application\Service\Document\DocumentLinePayloadFactory;
use App\Application\Service\Document\DocumentSnapshotFactory;
use App\Application\UseCase\Invoice\UpdateInvoiceTask;
use App\Application\UseCase\Invoice\UpdateInvoiceUseCase;
use App\Domain\Contracts\Repository\CustomerRepositoryInterface;
use App\Domain\Contracts\Repository\UserRepositoryInterface;
use App\Domain\Entity\Document\Invoice\Invoice;
use App\Tests\Factory\Customer\CustomerFactory;
use App\Tests\Factory\Document\Invoice\InvoiceFactory;
use App\Tests\Factory\User\UserFactory;
use App\Tests\Unit\Application\Stub\CustomerRepositoryStub;
use App\Tests\Unit\Application\Stub\EntityFetcherStub;
use App\Tests\Unit\Application\Stub\InvoiceRepositoryStub;
use App\Tests\Unit\Application\Stub\UserRepositoryStub;
use App\Tests\Unit\Application\Stub\WorkflowManagerStub;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @testType sociable-unit
 */
final class UpdateInvoiceHandlerTest extends TestCase
{
    use Factories;

    private UpdateInvoiceTask $task;

    protected function setUp(): void
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

        $this->task = new UpdateInvoiceTask(
            invoiceId: Uuid::v7()->toRfc4122(),
            input: $input
        );
    }

    public function test_handle_updates_invoice(): void
    {
        $invoice = InvoiceFactory::build()->draft()->create();

        $output = $this->createHandler($invoice)->handle($this->task);

        static::assertSame('Updated invoice', $output->title);
        static::assertSame('USD', $output->currency);
        static::assertSame('Updated subtitle', $output->subtitle);
        static::assertCount(1, $output->lines);
    }

    #[DataProvider('nonDraftInvoicesProvider')]
    public function test_handle_rejects_non_draft(Invoice $invoice): void
    {
        $this->expectException(DomainRuleViolationException::class);

        $this->createHandler($invoice)->handle($this->task);
    }

    public function test_handle_throws_when_customer_not_found(): void
    {
        $invoice = InvoiceFactory::build()->draft()->create();

        $customerRepository = static::createStub(CustomerRepositoryInterface::class);
        $customerRepository->method('findOneById')->willReturn(null);

        $this->expectException(ResourceNotFoundException::class);

        $this->createHandler($invoice, customerRepository: $customerRepository)->handle($this->task);
    }

    public function test_handle_throws_when_user_not_found(): void
    {
        $invoice = InvoiceFactory::build()->draft()->create();

        $userRepository = static::createStub(UserRepositoryInterface::class);
        $userRepository->method('findOneById')->willReturn(null);

        $this->expectException(ResourceNotFoundException::class);

        $this->createHandler($invoice, userRepository: $userRepository)->handle($this->task);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function createHandler(
        Invoice $invoice,
        ?UserRepositoryInterface $userRepository = null,
        ?CustomerRepositoryInterface $customerRepository = null,
    ): UpdateInvoiceUseCase {
        $invoiceRepository = new InvoiceRepositoryStub($invoice);
        $userRepository ??= new UserRepositoryStub(UserFactory::build()->create());
        $customerRepository ??= new CustomerRepositoryStub(CustomerFactory::build()->create());

        $linePayloadFactory = new DocumentLinePayloadFactory(new DocumentLineFactory());
        $payloadMapper = new InvoicePayloadMapper(new DocumentSnapshotFactory(), $linePayloadFactory);

        return new UpdateInvoiceUseCase(
            invoiceRepository: $invoiceRepository,
            payloadMapper: $payloadMapper,
            outputMapper: new InvoiceOutputMapper(),
            entityFetcher: EntityFetcherStub::create(
                userRepository: $userRepository,
                customerRepository: $customerRepository,
                invoiceRepository: $invoiceRepository
            ),
            workflowManager: WorkflowManagerStub::create()
        );
    }

    public static function nonDraftInvoicesProvider(): iterable
    {
        yield 'Issued invoice' => [
            InvoiceFactory::build()->issued()->create(),
        ];

        yield 'Overdue invoice' => [
            InvoiceFactory::build()->overdue()->create(),
        ];

        yield 'Paid invoice' => [
            InvoiceFactory::build()->paid()->create(),
        ];

        yield 'Voided invoice' => [
            InvoiceFactory::build()->voided()->create(),
        ];
    }
}
