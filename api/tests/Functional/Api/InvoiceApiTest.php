<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Invoice;
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
        $invoice = $this->entityManager->getRepository(Invoice::class)->find($data['id']);

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

    private function invoicePayload(Customer $customer): array
    {
        return [
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

    private function createInvoiceFixture(Customer $customer): void
    {
        $client = static::createClient();
        $token = $this->authenticate($client);

        $this->createInvoiceAndReturnId($client, $token, $customer);
    }

    private function createInvoiceAndReturnId(Client $client, string $token, Customer $customer): string
    {
        $response = $client->request('POST', '/api/invoices', [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->invoicePayload($customer),
        ]);

        self::assertResponseStatusCodeSame(201);
        $data = $response->toArray(false);

        return $data['id'];
    }

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
