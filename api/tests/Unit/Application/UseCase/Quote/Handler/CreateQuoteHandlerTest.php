<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Quote\Handler;

use App\Application\Dto\Quote\Input\Mapper\QuotePayloadMapper;
use App\Application\Dto\Quote\Input\QuoteInput;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Service\Document\DocumentLineFactory;
use App\Application\Service\Document\DocumentLinePayloadFactory;
use App\Application\Service\Document\DocumentSnapshotFactory;
use App\Application\UseCase\Quote\CreateQuoteUseCase;
use App\Application\UseCase\Quote\Dto\Output\Mapper\QuoteOutputMapper;
use App\Domain\Contracts\Repository\CustomerRepositoryInterface;
use App\Domain\Contracts\Repository\QuoteRepositoryInterface;
use App\Domain\Contracts\Repository\UserRepositoryInterface;
use App\Domain\Entity\Document\Quote\Quote;
use App\Tests\Factory\Customer\CustomerFactory;
use App\Tests\Factory\User\UserFactory;
use App\Tests\Unit\Application\Stub\CustomerRepositoryStub;
use App\Tests\Unit\Application\Stub\EntityFetcherStub;
use App\Tests\Unit\Application\Stub\QuoteRepositoryStub;
use App\Tests\Unit\Application\Stub\UserRepositoryStub;
use App\Tests\Unit\Application\Stub\WorkflowManagerStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @testType sociable-unit
 */
final class CreateQuoteHandlerTest extends TestCase
{
    use Factories;

    private QuoteInput $input;

    protected function setUp(): void
    {
        $this->input = new QuoteInput(
            title: 'New quote',
            currency: 'EUR',
            vatRate: 20,
            lines: [
                [
                    'description' => 'Design',
                    'quantity' => 2,
                    'rateUnit' => 'DAILY',
                    'rate' => 600,
                ],
            ],
            customerId: Uuid::v7()->toRfc4122(),
            subtitle: 'Phase 1',
        );

        $this->input->userId = Uuid::v7()->toRfc4122();
    }

    public function test_handle_persists_quote_and_returns_output(): void
    {
        $repository = new QuoteRepositoryStub();

        $output = $this->createHandler($repository)->handle($this->input);

        static::assertInstanceOf(Quote::class, $repository->findOneById(Uuid::v7()));
        static::assertSame('New quote', $output->title);
        static::assertSame('Phase 1', $output->subtitle);
        static::assertSame('EUR', $output->currency);
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

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function createHandler(
        ?QuoteRepositoryInterface $repository = null,
        ?UserRepositoryInterface $userRepository = null,
        ?CustomerRepositoryInterface $customerRepository = null,
    ): CreateQuoteUseCase {
        $repository = $repository ?? new QuoteRepositoryStub();
        $userRepository ??= new UserRepositoryStub(UserFactory::build()->create());
        $customerRepository ??= new CustomerRepositoryStub(CustomerFactory::build()->create());

        $payloadMapper = new QuotePayloadMapper(
            new DocumentSnapshotFactory(),
            new DocumentLinePayloadFactory(new DocumentLineFactory()),
        );

        return new CreateQuoteUseCase(
            quoteRepository: $repository,
            mapper: $payloadMapper,
            outputMapper: new QuoteOutputMapper(),
            entityFetcher: EntityFetcherStub::create(
                userRepository: $userRepository,
                customerRepository: $customerRepository,
            ),
            workflowManager: WorkflowManagerStub::create(),
        );
    }
}
