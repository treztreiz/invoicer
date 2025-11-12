<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Quote\Handler;

use App\Application\Exception\DomainRuleViolationException;
use App\Application\Service\Document\DocumentLineFactory;
use App\Application\Service\Document\DocumentLinePayloadFactory;
use App\Application\Service\Document\DocumentSnapshotFactory;
use App\Application\Service\EntityFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Quote\Handler\UpdateQuoteHandler;
use App\Application\UseCase\Quote\Input\Mapper\QuotePayloadMapper;
use App\Application\UseCase\Quote\Input\QuoteInput;
use App\Application\UseCase\Quote\Output\Mapper\QuoteOutputMapper;
use App\Application\UseCase\Quote\Task\UpdateQuoteTask;
use App\Domain\Contracts\CustomerRepositoryInterface;
use App\Domain\Contracts\QuoteRepositoryInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\DTO\DocumentLinePayload;
use App\Domain\DTO\QuotePayload;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Quote;
use App\Domain\Entity\User\User;
use App\Domain\Enum\RateUnit;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Company;
use App\Domain\ValueObject\CompanyLogo;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Quantity;
use App\Domain\ValueObject\VatRate;
use App\Tests\Unit\Application\UseCase\Common\QuoteRepositoryStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @testType solitary-unit
 */
final class UpdateQuoteHandlerTest extends TestCase
{
    public function test_handle_updates_quote(): void
    {
        $quote = $this->createQuote();

        $lineFactory = new DocumentLineFactory();
        $linePayloadFactory = new DocumentLinePayloadFactory($lineFactory);
        $quoteRepository = new QuoteRepositoryStub($quote);

        $handler = new UpdateQuoteHandler(
            quoteRepository: $quoteRepository,
            payloadMapper: new QuotePayloadMapper(new DocumentSnapshotFactory(), $linePayloadFactory),
            outputMapper: new QuoteOutputMapper(),
            entityFetcher: $this->entityFetcherForQuote($quoteRepository),
            workflowManager: $this->workflowManager($this->stubWorkflow())
        );

        $input = $this->quoteInput();
        $command = new UpdateQuoteTask(Uuid::v7()->toRfc4122(), $input);

        $output = $handler->handle($command);

        static::assertSame('Updated title', $output->title);
        static::assertSame('Updated subtitle', $output->subtitle);
        static::assertSame('NZD', $output->currency);
        static::assertCount(1, $output->lines);
    }

    public function test_handle_rejects_non_draft(): void
    {
        $quote = $this->createQuote();
        $quote->send(new \DateTimeImmutable());

        $lineFactory = new DocumentLineFactory();
        $linePayloadFactory = new DocumentLinePayloadFactory($lineFactory);
        $quoteRepository = new QuoteRepositoryStub($quote);

        $handler = new UpdateQuoteHandler(
            quoteRepository: $quoteRepository,
            payloadMapper: new QuotePayloadMapper(new DocumentSnapshotFactory(), $linePayloadFactory),
            outputMapper: new QuoteOutputMapper(),
            entityFetcher: $this->entityFetcherForQuote($quoteRepository),
            workflowManager: $this->workflowManager($this->stubWorkflow())
        );

        $input = $this->quoteInput();

        $this->expectException(DomainRuleViolationException::class);

        $handler->handle(new UpdateQuoteTask(Uuid::v7()->toRfc4122(), $input));
    }

    private function stubWorkflow(): WorkflowInterface
    {
        $workflow = static::createStub(WorkflowInterface::class);
        $workflow->method('getEnabledTransitions')->willReturn([]);

        return $workflow;
    }

    private function quoteInput(): QuoteInput
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

        return $input;
    }

    private function createQuote(): Quote
    {
        return Quote::fromPayload(
            new QuotePayload(
                title: 'Initial',
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
            )
        );
    }

    private function createCustomer(): Customer
    {
        return new Customer(
            name: new Name('Alice', 'Buyer'),
            contact: new Contact('alice@example.com', '+33123456789'),
            address: new Address('1 rue Test', null, '75000', 'Paris', null, 'FR'),
        );
    }

    private function createUser(): User
    {
        return new User(
            name: new Name('Admin', 'User'),
            contact: new Contact('admin@example.com', '+33102030405'),
            company: new Company(
                legalName: 'Acme Corp',
                logo: CompanyLogo::empty(),
                contact: new Contact('contact@acme.test', '+33987654321'),
                address: new Address('1 rue de Paris', null, '75000', 'Paris', null, 'FR'),
                defaultCurrency: 'EUR',
                defaultHourlyRate: new Money('100'),
                defaultDailyRate: new Money('800'),
                defaultVatRate: new VatRate('20'),
                legalMention: 'SIRET 123 456 789 00010',
            ),

            userIdentifier: 'admin@example.com',
            roles: ['ROLE_USER'],
            password: 'temp',
            locale: 'en_US',
        );
    }

    private function entityFetcherForQuote(QuoteRepositoryInterface $quoteRepository): EntityFetcher
    {
        $userRepositoryStub = static::createStub(UserRepositoryInterface::class);
        $userRepositoryStub->method('findOneById')->willReturn($this->createUser());

        $customerRepositoryStub = static::createStub(CustomerRepositoryInterface::class);
        $customerRepositoryStub->method('findOneById')->willReturn($this->createCustomer());

        $fetcher = new EntityFetcher();
        $fetcher->setUserRepository($userRepositoryStub);
        $fetcher->setCustomerRepository($customerRepositoryStub);
        $fetcher->setQuoteRepository($quoteRepository);

        return $fetcher;
    }

    private function workflowManager(WorkflowInterface $quoteWorkflow): DocumentWorkflowManager
    {
        $workflowManager = new DocumentWorkflowManager();
        $workflowManager->setQuoteWorkflow($quoteWorkflow);

        return $workflowManager;
    }
}
