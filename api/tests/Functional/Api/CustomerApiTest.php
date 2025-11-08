<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Domain\Entity\Customer\Customer;
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
use Zenstruck\Foundry\Test\ResetDatabase;

final class CustomerApiTest extends ApiTestCase
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
    public function test_list_customers_returns_active_customers(): void
    {
        $this->persistCustomer('Alice', 'Zephyr');
        $this->persistCustomer('Bob', 'Yellow');

        $client = static::createClient();
        $token = $this->authenticate($client);

        $response = $client->request('GET', '/api/customers', [
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertArrayHasKey('member', $data);
        static::assertCount(2, $data['member']);
        static::assertSame('Yellow', $data['member'][0]['lastName']);
        static::assertSame('Zephyr', $data['member'][1]['lastName']);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_create_customer_persists_entity(): void
    {
        $client = static::createClient();
        $token = $this->authenticate($client);

        $payload = [
            'firstName' => 'Charlie',
            'lastName' => 'Xavier',
            'email' => 'charlie.xavier@example.com',
            'phone' => '+33123456789',
            'address' => [
                'streetLine1' => '10 avenue de France',
                'postalCode' => '75013',
                'city' => 'Paris',
                'countryCode' => 'FR',
                'streetLine2' => null,
                'region' => null,
            ],
        ];

        $response = $client->request('POST', '/api/customers', [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $payload,
        ]);

        self::assertResponseStatusCodeSame(201);
        $data = $response->toArray(false);

        static::assertArrayHasKey('id', $data);
        static::assertSame('Charlie', $data['firstName']);
        static::assertSame('Xavier', $data['lastName']);

        $this->entityManager->clear();
        $repository = $this->entityManager->getRepository(Customer::class);
        $customer = $repository->find($data['id']);

        static::assertNotNull($customer);
        static::assertSame('charlie.xavier@example.com', $customer->contact->email);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_get_customer_returns_single_customer(): void
    {
        $customer = $this->persistCustomer('Diane', 'Watson');

        $client = static::createClient();
        $token = $this->authenticate($client);

        $response = $client->request('GET', sprintf('/api/customers/%s', $customer->id->toRfc4122()), [
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame('Diane', $data['firstName']);
        static::assertSame('Watson', $data['lastName']);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_update_customer_persists_changes(): void
    {
        $customer = $this->persistCustomer('Eve', 'Vector');

        $client = static::createClient();
        $token = $this->authenticate($client);

        $payload = [
            'firstName' => 'Evelyn',
            'lastName' => 'Vector',
            'email' => 'evelyn.vector@example.com',
            'phone' => '+33000000000',
            'address' => [
                'streetLine1' => '22 rue des Lilas',
                'streetLine2' => 'Appartement 4',
                'postalCode' => '31000',
                'city' => 'Toulouse',
                'region' => 'Occitanie',
                'countryCode' => 'FR',
            ],
        ];

        $response = $client->request('PUT', sprintf('/api/customers/%s', $customer->id->toRfc4122()), [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $payload,
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame('Evelyn', $data['firstName']);
        static::assertSame('evelyn.vector@example.com', $data['email']);
        static::assertSame('22 rue des Lilas', $data['address']['streetLine1']);

        $this->entityManager->clear();
        $repository = $this->entityManager->getRepository(Customer::class);
        $updated = $repository->find($customer->id);

        static::assertNotNull($updated);
        static::assertSame('Evelyn', $updated->name->firstName);
        static::assertSame('Occitanie', $updated->address->region);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_archive_customer_marks_it_archived(): void
    {
        $customer = $this->persistCustomer('Frank', 'Zero');

        $client = static::createClient();
        $token = $this->authenticate($client);

        $response = $client->request('POST', sprintf('/api/customers/%s/archive', $customer->id?->toRfc4122()), [
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertTrue($data['isArchived']);

        $this->entityManager->clear();
        $repository = $this->entityManager->getRepository(Customer::class);
        $archivedCustomer = $repository->find($customer->id);

        static::assertTrue($archivedCustomer?->isArchived);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_restore_customer_reactivates_it(): void
    {
        $customer = $this->persistCustomer('Grace', 'Young', archived: true);

        $client = static::createClient();
        $token = $this->authenticate($client);

        $response = $client->request('POST', sprintf('/api/customers/%s/restore', $customer->id?->toRfc4122()), [
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertFalse($data['isArchived']);

        $listResponse = $client->request('GET', '/api/customers', [
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        self::assertResponseIsSuccessful();
        $customers = $listResponse->toArray(false);

        $matching = array_filter($customers['member'], static fn (array $row) => $row['id'] === $customer->id?->toRfc4122());
        static::assertNotEmpty($matching);
    }

    private function persistCustomer(string $firstName, string $lastName, bool $archived = false): Customer
    {
        $customer = new Customer(
            name: new Name($firstName, $lastName),
            contact: new Contact(strtolower("$firstName.$lastName@example.com"), null),
            address: new Address('1 rue Test', null, '75000', 'Paris', null, 'FR')
        );

        if ($archived) {
            $customer->archive();
        }

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $customer;
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
}
