<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Invoice\Handler;

use App\Application\Dto\Invoice\Input\InvoiceInput;
use App\Application\Dto\Invoice\Input\Mapper\InvoicePayloadMapper;
use App\Application\Dto\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Service\Document\DocumentLineFactory;
use App\Application\Service\Document\DocumentLinePayloadFactory;
use App\Application\Service\Document\DocumentSnapshotFactory;
use App\Application\UseCase\Invoice\CreateInvoiceUseCase;
use App\Domain\Contracts\CustomerRepositoryInterface;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entity\Document\Invoice;
use App\Tests\Factory\Customer\CustomerFactory;
use App\Tests\Factory\User\UserFactory;
use App\Tests\Unit\Application\Stub\CustomerRepositoryStub;
use App\Tests\Unit\Application\Stub\EntityFetcherStub;
use App\Tests\Unit\Application\Stub\InvoiceRepositoryStub;
use App\Tests\Unit\Application\Stub\UserRepositoryStub;
use App\Tests\Unit\Application\Stub\WorkflowManagerStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @testType sociable-unit
 */
final class CreateInvoiceHandlerTest extends TestCase
{
    use Factories;

    private InvoiceInput $input;

    protected function setUp(): void
    {
        $this->input = new InvoiceInput(
            title: 'New invoice',
            currency: 'EUR',
            vatRate: 20,
            lines: [
                [
                    'description' => 'Development',
                    'quantity' => 5,
                    'rateUnit' => 'HOURLY',
                    'rate' => 100,
                ],
            ],
            customerId: Uuid::v7()->toRfc4122(),
            dueDate: '2025-03-01',
            subtitle: 'Phase 1',
        );

        $this->input->userId = Uuid::v7()->toRfc4122();
    }

    public function test_handle_persists_invoice_and_returns_output(): void
    {
        $repository = new InvoiceRepositoryStub();

        $output = $this->createHandler($repository)->handle($this->input);

        static::assertInstanceOf(Invoice::class, $repository->findOneById(Uuid::v7()));
        static::assertSame('New invoice', $output->title);
        static::assertSame('EUR', $output->currency);
        static::assertSame('Phase 1', $output->subtitle);
    }

    public function test_handle_throws_when_customer_not_found(): void
    {
        $customerRepository = static::createStub(CustomerRepositoryInterface::class);
        $customerRepository->method('findOneById')->willReturn(null);

        $this->expectException(ResourceNotFoundException::class);

        $this->createHandler(customerRepository: $customerRepository)->handle($this->input);
    }

    public function test_handle_throws_when_user_not_found(): void
    {
        $userRepository = static::createStub(UserRepositoryInterface::class);
        $userRepository->method('findOneById')->willReturn(null);

        $this->expectException(ResourceNotFoundException::class);

        $this->createHandler(userRepository: $userRepository)->handle($this->input);
    }

    private function createHandler(
        ?InvoiceRepositoryInterface $repository = null,
        ?UserRepositoryInterface $userRepository = null,
        ?CustomerRepositoryInterface $customerRepository = null,
    ): CreateInvoiceUseCase {
        $repository ??= new InvoiceRepositoryStub();
        $userRepository ??= new UserRepositoryStub(UserFactory::build()->create());
        $customerRepository ??= new CustomerRepositoryStub(CustomerFactory::build()->create());

        $payloadMapper = new InvoicePayloadMapper(
            new DocumentSnapshotFactory(),
            new DocumentLinePayloadFactory(new DocumentLineFactory()),
        );

        return new CreateInvoiceUseCase(
            invoiceRepository: $repository,
            mapper: $payloadMapper,
            outputMapper: new InvoiceOutputMapper(),
            entityFetcher: EntityFetcherStub::create(
                userRepository: $userRepository,
                customerRepository: $customerRepository,
            ),
            workflowManager: WorkflowManagerStub::create(),
        );
    }
}
