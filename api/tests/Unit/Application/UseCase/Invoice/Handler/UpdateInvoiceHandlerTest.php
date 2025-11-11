<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Invoice\Handler;

use App\Application\Exception\DomainRuleViolationException;
use App\Application\Service\Document\DocumentLineFactory;
use App\Application\Service\Document\DocumentLinePayloadFactory;
use App\Application\Service\Document\DocumentSnapshotFactory;
use App\Application\Service\EntityFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Invoice\Handler\UpdateInvoiceHandler;
use App\Application\UseCase\Invoice\Input\InvoiceInput;
use App\Application\UseCase\Invoice\Input\Mapper\InvoicePayloadMapper;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\UpdateInvoiceTask;
use App\Domain\Contracts\CustomerRepositoryInterface;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\DTO\DocumentLinePayload;
use App\Domain\DTO\InvoicePayload;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Invoice;
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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

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
            workflowManager: $this->workflowManager($this->stubWorkflow())
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
            workflowManager: $this->workflowManager($this->stubWorkflow())
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

    private function stubWorkflow(): WorkflowInterface
    {
        $workflow = static::createStub(WorkflowInterface::class);
        $workflow->method('getEnabledTransitions')->willReturn([]);

        return $workflow;
    }

    private function createCustomer(): Customer
    {
        return new Customer(
            name: new Name('Alice', 'Buyer'),
            contact: new Contact('alice@example.com', '+33123456789'),
            address: new Address('1 rue Test', null, '75000', 'Paris', null, 'FR')
        );
    }

    private function createUser(): User
    {
        return new User(
            name: new Name('Admin', 'User'),
            contact: new Contact('admin@example.com', '+33102030405'),
            company: new Company(
                legalName: 'Acme Corp',
                contact: new Contact('contact@acme.test', '+33987654321'),
                address: new Address('1 rue de Paris', null, '75000', 'Paris', null, 'FR'),
                defaultCurrency: 'EUR',
                defaultHourlyRate: new Money('100'),
                defaultDailyRate: new Money('800'),
                defaultVatRate: new VatRate('20'),
                legalMention: 'SIRET 123 456 789 00010'
            ),
            logo: CompanyLogo::empty(),
            userIdentifier: 'admin@example.com',
            roles: ['ROLE_USER'],
            password: 'temp',
            locale: 'en_US',
        );
    }

    private function entityFetcherForInvoice(InvoiceRepositoryInterface $invoiceRepository): EntityFetcher
    {
        $userRepositoryStub = static::createStub(UserRepositoryInterface::class);
        $userRepositoryStub->method('findOneById')->willReturn($this->createUser());

        $customerRepositoryStub = static::createStub(CustomerRepositoryInterface::class);
        $customerRepositoryStub->method('findOneById')->willReturn($this->createCustomer());

        $fetcher = new EntityFetcher();
        $fetcher->setUserRepository($userRepositoryStub);
        $fetcher->setCustomerRepository($customerRepositoryStub);
        $fetcher->setInvoiceRepository($invoiceRepository);

        return $fetcher;
    }

    private function workflowManager(WorkflowInterface $invoiceWorkflow): DocumentWorkflowManager
    {
        $workflowManager = new DocumentWorkflowManager();
        $workflowManager->setInvoiceWorkflow($invoiceWorkflow);

        return $workflowManager;
    }
}
