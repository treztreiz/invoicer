<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Quote;
use App\Domain\Entity\User\User;
use App\Domain\Enum\QuoteStatus;
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

final class QuoteApiTest extends ApiTestCase
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
    public function test_create_quote_persists_document(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');

        $client = static::createClient();
        $token = $this->authenticate($client);

        $payload = $this->quotePayload($customer);

        $response = $client->request('POST', '/api/quotes', [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $payload,
        ]);

        self::assertResponseStatusCodeSame(201);
        $data = $response->toArray(false);

        static::assertSame('Website revamp', $data['title']);
        static::assertSame('2400.00', $data['total']['gross']);
        static::assertSame(QuoteStatus::DRAFT->value, $data['status']);
        static::assertSame(['send'], $data['availableActions']);

        $this->entityManager->clear();
        $quote = $this->entityManager->getRepository(Quote::class)->find($data['quoteId']);

        static::assertInstanceOf(Quote::class, $quote);
        static::assertSame(QuoteStatus::DRAFT, $quote->status);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_list_quotes_returns_collection(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');
        $this->createQuoteFixture($customer);

        $client = static::createClient();
        $token = $this->authenticate($client);

        $response = $client->request('GET', '/api/quotes', [
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertNotEmpty($data);
        static::assertSame('Website revamp', $data['member'][0]['title']);
        static::assertNotEmpty($data['member'][0]['availableActions']);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_send_quote_transitions_to_sent(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');

        $client = static::createClient();
        $token = $this->authenticate($client);
        $quoteId = $this->createQuoteAndReturnId($client, $token, $customer);

        $response = $this->requestQuoteAction($client, $token, $quoteId, 'send');

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame(QuoteStatus::SENT->value, $data['status']);
        static::assertSame(['accept', 'reject'], $data['availableActions']);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_accept_quote_transitions_to_accepted(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');

        $client = static::createClient();
        $token = $this->authenticate($client);
        $quoteId = $this->createQuoteAndReturnId($client, $token, $customer);

        $this->requestQuoteAction($client, $token, $quoteId, 'send');
        $response = $this->requestQuoteAction($client, $token, $quoteId, 'accept');

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame(QuoteStatus::ACCEPTED->value, $data['status']);
        static::assertSame([], $data['availableActions']);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_reject_quote_transitions_to_rejected(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');

        $client = static::createClient();
        $token = $this->authenticate($client);
        $quoteId = $this->createQuoteAndReturnId($client, $token, $customer);

        $this->requestQuoteAction($client, $token, $quoteId, 'send');
        $response = $this->requestQuoteAction($client, $token, $quoteId, 'reject');

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame(QuoteStatus::REJECTED->value, $data['status']);
        static::assertSame([], $data['availableActions']);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_update_quote_mutates_document(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');

        $client = static::createClient();
        $token = $this->authenticate($client);
        $quoteId = $this->createQuoteAndReturnId($client, $token, $customer);

        $response = $client->request('PUT', sprintf('/api/quotes/%s', $quoteId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->quotePayload($customer, [
                'title' => 'Updated quote',
                'subtitle' => 'Updated scope',
                'currency' => 'USD',
                'lines' => [
                    [
                        'description' => 'Consulting',
                        'quantity' => 3,
                        'rateUnit' => 'DAILY',
                        'rate' => 750,
                    ],
                ],
            ]),
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame('Updated quote', $data['title']);
        static::assertSame('Updated scope', $data['subtitle']);
        static::assertSame('USD', $data['currency']);
        static::assertCount(1, $data['lines']);

        $this->entityManager->clear();
        $quote = $this->entityManager->getRepository(Quote::class)->find($quoteId);
        static::assertInstanceOf(Quote::class, $quote);
        static::assertSame('Updated quote', $quote->title);
        static::assertCount(1, $quote->lines);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_update_quote_rejected_when_not_draft(): void
    {
        $customer = $this->persistCustomer('Alice', 'Buyer');

        $client = static::createClient();
        $token = $this->authenticate($client);
        $quoteId = $this->createQuoteAndReturnId($client, $token, $customer);

        $this->requestQuoteAction($client, $token, $quoteId, 'send');

        $response = $client->request('PUT', sprintf('/api/quotes/%s', $quoteId), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->quotePayload($customer, ['title' => 'Should fail']),
        ]);

        self::assertResponseStatusCodeSame(400);
    }

    private function quotePayload(Customer $customer, array $override = []): array
    {
        $payload = [
            'title' => 'Website revamp',
            'subtitle' => 'Phase 1',
            'currency' => 'EUR',
            'vatRate' => 20,
            'customerId' => $customer->id?->toRfc4122(),
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
    private function createQuoteFixture(Customer $customer): void
    {
        $client = static::createClient();
        $token = $this->authenticate($client);

        $this->createQuoteAndReturnId($client, $token, $customer);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function createQuoteAndReturnId(Client $client, string $token, Customer $customer): string
    {
        $response = $client->request('POST', '/api/quotes', [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $this->quotePayload($customer),
        ]);

        self::assertResponseStatusCodeSame(201);
        $data = $response->toArray(false);

        return $data['quoteId'];
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function requestQuoteAction(Client $client, string $token, string $quoteId, string $action): ResponseInterface
    {
        return $client->request('POST', sprintf('/api/quotes/%s/actions', $quoteId), [
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
