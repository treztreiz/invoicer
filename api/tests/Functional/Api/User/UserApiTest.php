<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Tests\Functional\Api\User;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Factory\User\UserFactory;
use App\Tests\Functional\Api\Common\ApiClientHelperTrait;

/**
 * @testType functional
 */
final class UserApiTest extends ApiTestCase
{
    use ApiClientHelperTrait;

    private const string NEW_PASSWORD = 'BetterPass123!';

    protected static ?bool $alwaysBootKernel = true;

    public function test_get_me_returns_profile(): void
    {
        $client = $this->createAuthenticatedClient();
        $user = $this->authenticatedUser($client);

        $response = $this->apiRequest($client, 'GET', '/api/me');

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertNotNull($user);
        static::assertSame($user->userIdentifier, $data['email']);
        static::assertSame($user->locale, $data['locale']);
        static::assertSame($user->company->legalName, $data['company']['legalName']);
        static::assertArrayHasKey('logoUrl', $data);
        static::assertNull($data['logoUrl']);
    }

    public function test_update_me_persists_changes(): void
    {
        $client = $this->createAuthenticatedClient();

        $response = $this->apiRequest($client, 'PUT', '/api/me', [
            'json' => $this->createUserPayload(),
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame('Jane', $data['firstName']);
        static::assertSame('Doe', $data['lastName']);
        static::assertSame('jane.doe@example.com', $data['email']);
        static::assertSame('New Corp', $data['company']['legalName']);
        static::assertSame('99 avenue de France', $data['company']['address']['streetLine1']);
        static::assertSame('960.00', $data['company']['defaultDailyRate']);

        UserFactory::assert()->exists([
            'userIdentifier' => 'jane.doe@example.com',
            'locale' => 'fr_FR',
            'company.legalName' => 'New Corp',
        ]);
    }

    public function test_update_me_missing_required_field_returns_validation_error(): void
    {
        $client = $this->createAuthenticatedClient();

        $payload = $this->createUserPayload();
        unset($payload['email']); // email intentionally omitted

        $response = $this->apiRequest($client, 'PUT', '/api/me', [
            'json' => $payload,
        ]);

        self::assertResponseStatusCodeSame(422);
        $data = $response->toArray(false);

        static::assertArrayHasKey('violations', $data);
        static::assertSame('email', $data['violations'][0]['propertyPath']);
        static::assertSame('This value should not be blank.', $data['violations'][0]['message']);
    }

    public function test_change_password_updates_credentials(): void
    {
        $client = $this->createAuthenticatedClient();
        $userIdentifier = $this->authenticatedUser($client)?->userIdentifier;

        $response = $this->apiRequest($client, 'POST', '/api/me/password', [
            'json' => [
                'currentPassword' => UserFactory::PLAIN_PASSWORD,
                'newPassword' => self::NEW_PASSWORD,
                'confirmPassword' => self::NEW_PASSWORD,
            ],
        ]);

        self::assertResponseStatusCodeSame(204);
        static::assertSame('', $response->getContent());

        $this->apiRequest($client, 'POST', '/api/auth/login', [
            'json' => [
                'userIdentifier' => $userIdentifier,
                'password' => UserFactory::PLAIN_PASSWORD,
            ],
        ], authenticated: false);

        self::assertResponseStatusCodeSame(401);

        $this->apiRequest($client, 'POST', '/api/auth/login', [
            'json' => [
                'userIdentifier' => $userIdentifier,
                'password' => self::NEW_PASSWORD,
            ],
        ], authenticated: false);

        self::assertResponseIsSuccessful();
    }

    public function test_change_password_with_invalid_current_password_returns_validation_error(): void
    {
        $client = $this->createAuthenticatedClient();

        $response = $this->apiRequest($client, 'POST', '/api/me/password', [
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

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function createUserPayload(): array
    {
        return [
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
    }
}
