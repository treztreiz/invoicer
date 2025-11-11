<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use App\Domain\Entity\User\User;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Company;
use App\Domain\ValueObject\CompanyLogo;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\VatRate;
use App\Infrastructure\Security\SecurityUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Zenstruck\Foundry\Test\ResetDatabase;

final class InvoiceApiTest extends ApiTestCase
{
    use ResetDatabase;

    private const string PASSWORD = 'Password123!';

    protected static ?bool $alwaysBootKernel = false;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $user = new User(
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

        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user->password = $passwordHasher->hashPassword(new SecurityUser($user), self::PASSWORD);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_create_invoice_persists_document(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');

        $client = static::createClient();
        $token = $this->authenticate($client);

        $payload = $this->invoicePayload($customer);

        $response = $client->request('POST', '/api/invoices', [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $payload,
        ]);

        self::assertResponseStatusCodeSame(201);
        $data = $response->toArray(false);

        static::assertSame('Website revamp', $data['title']);
        static::assertSame('2400.00', $data['total']['gross']);
        static::assertSame($payload['dueDate'], $data['dueDate']);
        static::assertSame('DRAFT', $data['status']);

        $this->entityManager->clear();
        $invoice = $this->entityManager->getRepository(Invoice::class)->find($data['invoiceId']);

        static::assertInstanceOf(Invoice::class, $invoice);
        static::assertSame('DRAFT', $invoice->status->value);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_list_invoices_returns_collection(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');
        $this->createInvoiceFixture($customer);

        $client = static::createClient();
        $token = $this->authenticate($client);

        $response = $client->request('GET', '/api/invoices', [
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertNotEmpty($data);
        static::assertSame('Website revamp', $data['member'][0]['title']);
    }

    private function invoicePayload(Customer $customer, array $override = []): array
    {
        $payload = [
            'title' => 'Website revamp',
            'subtitle' => 'Phase 1',
            'currency' => 'EUR',
            'vatRate' => 20,
            'customerId' => $customer->id?->toRfc4122(),
            'dueDate' => new \DateTimeImmutable('+1 week')->format('Y-m-d'),
            'lines' => [
                [
                    'description' => 'Development',
                    'quantity' => 10,
                    'rateUnit' => 'HOURLY',
                    'rate' => 80,
                ],
                [
                    'description' => 'Workshop',
                    'quantity' => 2,
                    'rateUnit' => 'DAILY',
                    'rate' => 600,
                ],
            ],
        ];

        return array_replace($payload, $override);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_issue_invoice_transitions_to_issued(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');

        $client = static::createClient();
        $token = $this->authenticate($client);

        $invoiceId = $this->createInvoiceAndReturnId($client, $token, $customer);

        $response = $this->requestInvoiceAction($client, $token, $invoiceId, 'issue');

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame('ISSUED', $data['status']);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_mark_paid_transitions_to_paid(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');

        $client = static::createClient();
        $token = $this->authenticate($client);

        $invoiceId = $this->createInvoiceAndReturnId($client, $token, $customer);

        $this->requestInvoiceAction($client, $token, $invoiceId, 'issue');
        $response = $this->requestInvoiceAction($client, $token, $invoiceId, 'mark_paid');

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame('PAID', $data['status']);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_update_invoice_mutates_document(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');

        $client = static::createClient();
        $token = $this->authenticate($client);
        $invoiceId = $this->createInvoiceAndReturnId($client, $token, $customer);

        $response = $client->request('PUT', sprintf('/api/invoices/%s', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->invoicePayload($customer, [
                'title' => 'Updated invoice',
                'subtitle' => 'Updated phase',
                'currency' => 'USD',
                'dueDate' => new \DateTimeImmutable('+2 weeks')->format('Y-m-d'),
                'lines' => [
                    [
                        'description' => 'Consulting',
                        'quantity' => 5,
                        'rateUnit' => 'DAILY',
                        'rate' => 700,
                    ],
                ],
            ]),
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame('Updated invoice', $data['title']);
        static::assertSame('Updated phase', $data['subtitle']);
        static::assertSame('USD', $data['currency']);
        static::assertCount(1, $data['lines']);

        $this->entityManager->clear();
        $invoice = $this->entityManager->getRepository(Invoice::class)->find($invoiceId);
        static::assertInstanceOf(Invoice::class, $invoice);
        static::assertSame('Updated invoice', $invoice->title);
        static::assertCount(1, $invoice->lines);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_update_invoice_rejected_when_not_draft(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');

        $client = static::createClient();
        $token = $this->authenticate($client);
        $invoiceId = $this->createInvoiceAndReturnId($client, $token, $customer);

        $this->requestInvoiceAction($client, $token, $invoiceId, 'issue');

        $client->request('PUT', sprintf('/api/invoices/%s', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->invoicePayload($customer, ['title' => 'Should fail']),
        ]);

        self::assertResponseStatusCodeSame(400);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_attach_invoice_recurrence(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');
        $client = static::createClient();
        $token = $this->authenticate($client);
        $invoiceId = $this->createInvoiceAndReturnId($client, $token, $customer);

        $response = $client->request('POST', sprintf('/api/invoices/%s/recurrence', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->recurrencePayload(),
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame('MONTHLY', $data['recurrence']['frequency']);
        static::assertSame('2025-01-01', $data['recurrence']['anchorDate']);

        $this->entityManager->clear();
        $invoice = $this->entityManager->getRepository(Invoice::class)->find($invoiceId);

        static::assertInstanceOf(Invoice::class, $invoice);
        static::assertNotNull($invoice->recurrence);
        static::assertSame('MONTHLY', $invoice->recurrence->frequency->value);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_update_invoice_recurrence(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');
        $client = static::createClient();
        $token = $this->authenticate($client);
        $invoiceId = $this->createInvoiceAndReturnId($client, $token, $customer);

        $client->request('POST', sprintf('/api/invoices/%s/recurrence', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->recurrencePayload(),
        ]);
        self::assertResponseIsSuccessful();

        $response = $client->request('PUT', sprintf('/api/invoices/%s/recurrence', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->recurrencePayload([
                'frequency' => 'QUARTERLY',
                'interval' => 3,
                'anchorDate' => '2025-02-01',
            ]),
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame('QUARTERLY', $data['recurrence']['frequency']);
        static::assertSame(3, $data['recurrence']['interval']);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_delete_invoice_recurrence(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');
        $client = static::createClient();
        $token = $this->authenticate($client);
        $invoiceId = $this->createInvoiceAndReturnId($client, $token, $customer);

        $client->request('POST', sprintf('/api/invoices/%s/recurrence', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->recurrencePayload(),
        ]);
        self::assertResponseIsSuccessful();

        $response = $client->request('DELETE', sprintf('/api/invoices/%s/recurrence', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertNull($data['recurrence']);

        $this->entityManager->clear();
        $invoice = $this->entityManager->getRepository(Invoice::class)->find($invoiceId);

        static::assertInstanceOf(Invoice::class, $invoice);
        static::assertNull($invoice->recurrence);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_attach_invoice_recurrence_rejected_when_installment_plan_exists(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');
        $client = static::createClient();
        $token = $this->authenticate($client);
        $invoiceId = $this->createInvoiceAndReturnId($client, $token, $customer);

        $invoice = $this->entityManager->getRepository(Invoice::class)->find($invoiceId);
        static::assertInstanceOf(Invoice::class, $invoice);
        $invoice->attachInstallmentPlan(new InstallmentPlan());
        $this->entityManager->flush();

        $response = $client->request('POST', sprintf('/api/invoices/%s/recurrence', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->recurrencePayload(),
        ]);

        self::assertResponseStatusCodeSame(400);
        $data = $response->toArray(false);
        static::assertStringContainsString('both a recurrence and an installment plan', $data['detail'] ?? '');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_attach_invoice_recurrence_rejected_when_invoice_generated_from_recurrence(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');
        $client = static::createClient();
        $token = $this->authenticate($client);
        $invoiceId = $this->createInvoiceAndReturnId($client, $token, $customer);

        $invoice = $this->entityManager->getRepository(Invoice::class)->find($invoiceId);
        static::assertInstanceOf(Invoice::class, $invoice);
        $invoice->markGeneratedFromRecurrence(Uuid::v7());
        $this->entityManager->flush();

        $response = $client->request('POST', sprintf('/api/invoices/%s/recurrence', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->recurrencePayload(),
        ]);

        self::assertResponseStatusCodeSame(400);
        $data = $response->toArray(false);
        static::assertStringContainsString('Generated invoices cannot attach new scheduling rules.', $data['detail'] ?? '');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_attach_invoice_recurrence_rejected_when_invoice_generated_from_installment(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');
        $client = static::createClient();
        $token = $this->authenticate($client);
        $invoiceId = $this->createInvoiceAndReturnId($client, $token, $customer);

        $invoice = $this->entityManager->getRepository(Invoice::class)->find($invoiceId);
        static::assertInstanceOf(Invoice::class, $invoice);
        $invoice->markGeneratedFromInstallment(Uuid::v7());
        $this->entityManager->flush();

        $response = $client->request('POST', sprintf('/api/invoices/%s/recurrence', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->recurrencePayload(),
        ]);

        self::assertResponseStatusCodeSame(400);
        $data = $response->toArray(false);
        static::assertStringContainsString('Generated invoices cannot attach new scheduling rules.', $data['detail'] ?? '');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_attach_installment_plan(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');
        $client = static::createClient();
        $token = $this->authenticate($client);
        $invoiceId = $this->createInvoiceAndReturnId($client, $token, $customer);

        $response = $client->request('POST', sprintf('/api/invoices/%s/installment-plan', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->installmentPayload(),
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);
        static::assertCount(2, $data['installmentPlan']['installments']);

        $this->entityManager->clear();
        $invoice = $this->entityManager->getRepository(Invoice::class)->find($invoiceId);
        static::assertInstanceOf(Invoice::class, $invoice);
        static::assertNotNull($invoice->installmentPlan);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_update_installment_plan(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');
        $client = static::createClient();
        $token = $this->authenticate($client);
        $invoiceId = $this->createInvoiceAndReturnId($client, $token, $customer);

        $client->request('POST', sprintf('/api/invoices/%s/installment-plan', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->installmentPayload(),
        ]);
        self::assertResponseIsSuccessful();

        $response = $client->request('PUT', sprintf('/api/invoices/%s/installment-plan', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->installmentPayload([
                ['percentage' => 40, 'dueDate' => '2025-01-15'],
                ['percentage' => 60, 'dueDate' => '2025-02-15'],
            ]),
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);
        static::assertSame('60.00', $data['installmentPlan']['installments'][1]['percentage']);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_delete_installment_plan(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');
        $client = static::createClient();
        $token = $this->authenticate($client);
        $invoiceId = $this->createInvoiceAndReturnId($client, $token, $customer);

        $client->request('POST', sprintf('/api/invoices/%s/installment-plan', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->installmentPayload(),
        ]);
        self::assertResponseIsSuccessful();

        $response = $client->request('DELETE', sprintf('/api/invoices/%s/installment-plan', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);
        static::assertNull($data['installmentPlan']);

        $this->entityManager->clear();
        $invoice = $this->entityManager->getRepository(Invoice::class)->find($invoiceId);
        static::assertInstanceOf(Invoice::class, $invoice);
        static::assertNull($invoice->installmentPlan);
    }

    /**
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function test_attach_installment_plan_rejected_when_recurrence_exists(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');
        $client = static::createClient();
        $token = $this->authenticate($client);
        $invoiceId = $this->createInvoiceAndReturnId($client, $token, $customer);

        $client->request('POST', sprintf('/api/invoices/%s/recurrence', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->recurrencePayload(),
        ]);
        self::assertResponseIsSuccessful();

        $response = $client->request('POST', sprintf('/api/invoices/%s/installment-plan', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->installmentPayload(),
        ]);

        self::assertResponseStatusCodeSame(400);
        $data = $response->toArray(false);
        static::assertStringContainsString('both a recurrence and an installment plan', $data['detail'] ?? '');
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function test_attach_installment_plan_rejected_when_invoice_generated_from_recurrence(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');
        $client = static::createClient();
        $token = $this->authenticate($client);
        $invoiceId = $this->createInvoiceAndReturnId($client, $token, $customer);

        $invoice = $this->entityManager->getRepository(Invoice::class)->find($invoiceId);
        static::assertInstanceOf(Invoice::class, $invoice);
        $invoice->markGeneratedFromRecurrence(Uuid::v7());
        $this->entityManager->flush();

        $response = $client->request('POST', sprintf('/api/invoices/%s/installment-plan', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->installmentPayload(),
        ]);

        self::assertResponseStatusCodeSame(400);
        $data = $response->toArray(false);
        static::assertStringContainsString('Generated invoices cannot attach new scheduling rules.', $data['detail'] ?? '');
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function test_attach_installment_plan_rejected_when_invoice_generated_from_installment(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');
        $client = static::createClient();
        $token = $this->authenticate($client);
        $invoiceId = $this->createInvoiceAndReturnId($client, $token, $customer);

        $invoice = $this->entityManager->getRepository(Invoice::class)->find($invoiceId);
        static::assertInstanceOf(Invoice::class, $invoice);
        $invoice->markGeneratedFromInstallment(Uuid::v7());
        $this->entityManager->flush();

        $response = $client->request('POST', sprintf('/api/invoices/%s/installment-plan', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->installmentPayload(),
        ]);

        self::assertResponseStatusCodeSame(400);
        $data = $response->toArray(false);
        static::assertStringContainsString('Generated invoices cannot attach new scheduling rules.', $data['detail'] ?? '');
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    private function createInvoiceFixture(Customer $customer): void
    {
        $client = static::createClient();
        $token = $this->authenticate($client);

        $this->createInvoiceAndReturnId($client, $token, $customer);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function createInvoiceAndReturnId(Client $client, string $token, Customer $customer): string
    {
        $response = $client->request('POST', '/api/invoices', [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->invoicePayload($customer),
        ]);

        self::assertResponseStatusCodeSame(201);
        $data = $response->toArray(false);

        return $data['invoiceId'];
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function requestInvoiceAction(Client $client, string $token, string $invoiceId, string $action): ResponseInterface
    {
        return $client->request('POST', sprintf('/api/invoices/%s/actions', $invoiceId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => ['action' => $action],
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function authenticate(Client $client): string
    {
        $response = $client->request('POST', '/api/auth/login', [
            'json' => [
                'userIdentifier' => 'admin@example.com',
                'password' => self::PASSWORD,
            ],
        ]);

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);

        return $data['token'];
    }

    private function recurrencePayload(array $override = []): array
    {
        return array_merge([
            'frequency' => 'MONTHLY',
            'interval' => 1,
            'anchorDate' => '2025-01-01',
            'endStrategy' => 'UNTIL_DATE',
            'endDate' => '2025-12-31',
            'occurrenceCount' => null,
        ], $override);
    }

    private function installmentPayload(array $installments = []): array
    {
        if ([] === $installments) {
            $installments = [
                ['percentage' => 50, 'dueDate' => '2025-01-01'],
                ['percentage' => 50, 'dueDate' => '2025-02-01'],
            ];
        }

        return [
            'installments' => array_values($installments),
        ];
    }

    private function persistCustomer(string $firstName, string $lastName): Customer
    {
        $customer = new Customer(
            name: new Name($firstName, $lastName),
            contact: new Contact(strtolower("$firstName.$lastName@example.com"), '+33123456789'),
            address: new Address('1 rue Test', null, '75000', 'Paris', null, 'FR')
        );

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $customer;
    }
}
