<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
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

final class MeApiTest extends ApiTestCase
{
    use ResetDatabase;

    private const string PASSWORD = 'Password123!';
    private const string NEW_PASSWORD = 'BetterPass123!';

    protected static ?bool $alwaysBootKernel = false;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $user = new User(
            name: new Name('John', 'Doe'),
            contact: new Contact('john.doe@example.com', '+33102030405'),
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
            userIdentifier: 'john.doe@example.com',
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
    public function test_get_me_returns_profile(): void
    {
        $client = static::createClient();
        $token = $this->authenticate($client);

        $response = $client->request('GET', '/api/me', [
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame('john.doe@example.com', $data['email']);
        static::assertSame('en_US', $data['locale']);
        static::assertSame('Acme Corp', $data['company']['legalName']);
        static::assertArrayHasKey('logoUrl', $data['company']);
        static::assertNull($data['company']['logoUrl']);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_update_me_persists_changes(): void
    {
        $client = static::createClient();
        $token = $this->authenticate($client);

        $payload = [
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'email' => 'jane.doe@example.com',
            'phone' => '+33111111111',
            'locale' => 'fr_FR',
            'company' => [
                'legalName' => 'New Corp',
                'email' => 'hello@newcorp.test',
                'phone' => '+33222222222',
                'address' => [
                    'streetLine1' => '99 avenue de France',
                    'streetLine2' => 'Bâtiment B',
                    'postalCode' => '69000',
                    'city' => 'Lyon',
                    'region' => 'Auvergne-Rhône-Alpes',
                    'countryCode' => 'FR',
                ],
                'defaultCurrency' => 'EUR',
                'defaultHourlyRate' => '120',
                'defaultDailyRate' => '960',
                'defaultVatRate' => '20',
                'legalMention' => 'TVA FR',
            ],
        ];

        $response = $client->request('PUT', '/api/me', [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $payload,
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame('Jane', $data['firstName']);
        static::assertSame('Doe', $data['lastName']);
        static::assertSame('jane.doe@example.com', $data['email']);
        static::assertSame('New Corp', $data['company']['legalName']);
        static::assertSame('99 avenue de France', $data['company']['address']['streetLine1']);
        static::assertSame('960.00', $data['company']['defaultDailyRate']);

        $this->entityManager->clear();
        $repository = $this->entityManager->getRepository(User::class);
        /** @var User|null $user */
        $user = $repository->findOneBy(['userIdentifier' => 'jane.doe@example.com']);

        static::assertNotNull($user);
        static::assertSame('fr_FR', $user->locale);
        static::assertSame('New Corp', $user->company->legalName());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_update_me_missing_required_field_returns_validation_error(): void
    {
        $client = static::createClient();
        $token = $this->authenticate($client);

        $payload = [
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            // email intentionally omitted
            'phone' => '+33111111111',
            'locale' => 'fr_FR',
            'company' => [
                'legalName' => 'New Corp',
                'email' => 'hello@newcorp.test',
                'phone' => '+33222222222',
                'address' => [
                    'streetLine1' => '99 avenue de France',
                    'streetLine2' => 'Bâtiment B',
                    'postalCode' => '69000',
                    'city' => 'Lyon',
                    'region' => 'Auvergne-Rhône-Alpes',
                    'countryCode' => 'FR',
                ],
                'defaultCurrency' => 'EUR',
                'defaultHourlyRate' => '120',
                'defaultDailyRate' => '960',
                'defaultVatRate' => '20',
                'legalMention' => 'TVA FR',
            ],
        ];

        $response = $client->request('PUT', '/api/me', [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => $payload,
        ]);

        self::assertResponseStatusCodeSame(422);
        $data = $response->toArray(false);

        static::assertArrayHasKey('violations', $data);
        static::assertSame('email', $data['violations'][0]['propertyPath']);
        static::assertSame('This value should not be blank.', $data['violations'][0]['message']);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_change_password_updates_credentials(): void
    {
        $client = static::createClient();
        $token = $this->authenticate($client);

        $response = $client->request('POST', '/api/me/password', [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => [
                'currentPassword' => self::PASSWORD,
                'newPassword' => self::NEW_PASSWORD,
                'confirmPassword' => self::NEW_PASSWORD,
            ],
        ]);

        self::assertResponseStatusCodeSame(204);
        static::assertSame('', $response->getContent());

        $client->request('POST', '/api/auth/login', [
            'json' => [
                'userIdentifier' => 'john.doe@example.com',
                'password' => self::PASSWORD,
            ],
        ]);

        self::assertResponseStatusCodeSame(401);

        $client->request('POST', '/api/auth/login', [
            'json' => [
                'userIdentifier' => 'john.doe@example.com',
                'password' => self::NEW_PASSWORD,
            ],
        ]);

        self::assertResponseIsSuccessful();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_change_password_with_invalid_current_password_returns_validation_error(): void
    {
        $client = static::createClient();
        $token = $this->authenticate($client);

        $response = $client->request('POST', '/api/me/password', [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => [
                'currentPassword' => 'wrong-password',
                'newPassword' => self::NEW_PASSWORD,
                'confirmPassword' => self::NEW_PASSWORD,
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        $data = $response->toArray(false);

        static::assertSame('currentPassword', $data['violations'][0]['propertyPath']);
        static::assertSame('Current password is invalid.', $data['violations'][0]['message']);
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
                'userIdentifier' => 'john.doe@example.com',
                'password' => self::PASSWORD,
            ],
        ]);

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);

        return $data['token'];
    }
}
