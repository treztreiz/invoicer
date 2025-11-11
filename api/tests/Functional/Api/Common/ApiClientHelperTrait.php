<?php

/**
 * @noinspection PhpUndefinedMethodInspection
 * @noinspection PhpUnhandledExceptionInspection
 */

declare(strict_types=1);

namespace App\Tests\Functional\Api\Common;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Domain\Entity\User\User;
use App\Tests\Factory\User\UserFactory;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Utility helpers for authenticating API clients and issuing requests with consistent headers.
 */
trait ApiClientHelperTrait
{
    use Factories;
    use ResetDatabase;

    /**
     * @var \SplObjectStorage<Client, array{token: string, user: User}>|null
     */
    private ?\SplObjectStorage $authenticatedClients = null;

    protected function createAuthenticatedClient(?User $user = null): Client
    {
        $client = static::createClient();
        $this->authenticateClient($client, $user);

        return $client;
    }

    protected function authenticateClient(Client $client, ?User $user = null): string
    {
        $user ??= UserFactory::createOne();

        $response = $client->request('POST', '/api/auth/login', [
            'json' => [
                'userIdentifier' => $user->userIdentifier,
                'password' => UserFactory::PLAIN_PASSWORD,
            ],
        ]);

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        $token = $data['token'];

        $this->rememberAuthenticatedClient($client, $user, $token);

        return $token;
    }

    protected function apiRequest(
        Client $client,
        string $method,
        string $uri,
        array $options = [],
        bool $authenticated = true,
        ?User $asUser = null,
    ): ResponseInterface {
        if ($authenticated) {
            $headers = $options['headers'] ?? [];

            if (!array_key_exists('Authorization', $headers)) {
                $token = $this->tokenForClient($client, $asUser);
                $headers['Authorization'] = 'Bearer '.$token;
            }

            $options['headers'] = $headers;
        }

        return $client->request($method, $uri, $options);
    }

    protected function authenticatedUser(Client $client): ?User
    {
        $storage = $this->authenticatedClientsStorage();

        return $storage->contains($client) ? $storage[$client]['user'] : null;
    }

    private function tokenForClient(Client $client, ?User $user = null): string
    {
        $storage = $this->authenticatedClientsStorage();

        if (null !== $user) {
            return $this->authenticateClient($client, $user);
        }

        if ($storage->contains($client)) {
            return $storage[$client]['token'];
        }

        return $this->authenticateClient($client);
    }

    private function rememberAuthenticatedClient(Client $client, User $user, string $token): void
    {
        $this->authenticatedClientsStorage()[$client] = [
            'token' => $token,
            'user' => $user,
        ];
    }

    private function authenticatedClientsStorage(): \SplObjectStorage
    {
        return $this->authenticatedClients ??= new \SplObjectStorage();
    }
}
