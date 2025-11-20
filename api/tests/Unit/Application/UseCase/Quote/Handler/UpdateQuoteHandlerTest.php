<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Quote\Handler;

use App\Application\Dto\Quote\Input\Mapper\QuotePayloadMapper;
use App\Application\Dto\Quote\Input\QuoteInput;
use App\Application\Exception\DomainRuleViolationException;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Service\Document\DocumentLineFactory;
use App\Application\Service\Document\DocumentLinePayloadFactory;
use App\Application\Service\Document\DocumentSnapshotFactory;
use App\Application\UseCase\Quote\Dto\Output\Mapper\QuoteOutputMapper;
use App\Application\UseCase\Quote\UpdateQuoteTask;
use App\Domain\Contracts\Repository\CustomerRepositoryInterface;
use App\Domain\Contracts\Repository\UserRepositoryInterface;
use App\Domain\Entity\Document\Quote;
use App\Tests\Factory\Customer\CustomerFactory;
use App\Tests\Factory\Document\QuoteFactory;
use App\Tests\Factory\User\UserFactory;
use App\Tests\Unit\Application\Stub\CustomerRepositoryStub;
use App\Tests\Unit\Application\Stub\EntityFetcherStub;
use App\Tests\Unit\Application\Stub\QuoteRepositoryStub;
use App\Tests\Unit\Application\Stub\UserRepositoryStub;
use App\Tests\Unit\Application\Stub\WorkflowManagerStub;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @testType sociable-unit
 */
final class UpdateQuoteHandlerTest extends TestCase
{
    use Factories;

    private UpdateQuoteTask $task;

    protected function setUp(): void
    {
        $input = new QuoteInput(
            title: 'Updated title',
            currency: 'NZD',
            vatRate: 15,
            lines: [
                [
                    'description' => 'Consulting',
                    'quantity' => 1,
                    'rateUnit' => 'DAILY',
                    'rate' => 900,
                ],
            ],
            customerId: Uuid::v7()->toRfc4122(),
            subtitle: 'Updated subtitle',
        );

        $input->userId = Uuid::v7()->toRfc4122();

        $this->task = new UpdateQuoteTask(
            quoteId: Uuid::v7()->toRfc4122(),
            input: $input
        );
    }

    public function test_handle_updates_quote(): void
    {
        $quote = QuoteFactory::build()->draft()->create();

        $output = $this->createHandler($quote)->handle($this->task);

        static::assertSame('Updated title', $output->title);
        static::assertSame('Updated subtitle', $output->subtitle);
        static::assertSame('NZD', $output->currency);
        static::assertCount(1, $output->lines);
    }

    #[DataProvider('nonDraftQuotesProvider')]
    public function test_handle_rejects_non_draft(Quote $quote): void
    {
        $this->expectException(DomainRuleViolationException::class);

        $this->createHandler($quote)->handle($this->task);
    }

    public function test_handle_throws_when_customer_not_found(): void
    {
        $quote = QuoteFactory::build()->draft()->create();

        $customerRepository = static::createStub(CustomerRepositoryInterface::class);
        $customerRepository->method('findOneById')->willReturn(null);

        $this->expectException(ResourceNotFoundException::class);

        $this->createHandler($quote, customerRepository: $customerRepository)->handle($this->task);
    }

    public function test_handle_throws_when_user_not_found(): void
    {
        $quote = QuoteFactory::build()->draft()->create();

        $userRepository = static::createStub(UserRepositoryInterface::class);
        $userRepository->method('findOneById')->willReturn(null);

        $this->expectException(ResourceNotFoundException::class);

        $this->createHandler($quote, userRepository: $userRepository)->handle($this->task);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function createHandler(
        Quote $quote,
        ?UserRepositoryInterface $userRepository = null,
        ?CustomerRepositoryInterface $customerRepository = null,
    ): \App\Application\UseCase\Quote\UpdateQuoteUseCase {
        $quoteRepository = new QuoteRepositoryStub($quote);
        $userRepository ??= new UserRepositoryStub(UserFactory::build()->create());
        $customerRepository ??= new CustomerRepositoryStub(CustomerFactory::build()->create());

        $linePayloadFactory = new DocumentLinePayloadFactory(new DocumentLineFactory());
        $payloadMapper = new QuotePayloadMapper(new DocumentSnapshotFactory(), $linePayloadFactory);

        return new \App\Application\UseCase\Quote\UpdateQuoteUseCase(
            quoteRepository: $quoteRepository,
            payloadMapper: $payloadMapper,
            outputMapper: new QuoteOutputMapper(),
            entityFetcher: EntityFetcherStub::create(
                userRepository: $userRepository,
                customerRepository: $customerRepository,
                quoteRepository: $quoteRepository
            ),
            workflowManager: WorkflowManagerStub::create()
        );
    }

    public static function nonDraftQuotesProvider(): iterable
    {
        yield 'Sent quote' => [
            QuoteFactory::build()->sent()->create(),
        ];

        yield 'Accepted quote' => [
            QuoteFactory::build()->accepted()->create(),
        ];

        yield 'Rejected quote' => [
            QuoteFactory::build()->rejected()->create(),
        ];
    }
}
