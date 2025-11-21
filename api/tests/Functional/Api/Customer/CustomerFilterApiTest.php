<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Tests\Functional\Api\Customer;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Factory\Customer\CustomerFactory;
use App\Tests\Factory\ValueObject\NameFactory;
use App\Tests\Functional\Api\Common\ApiClientHelperTrait;

use function Zenstruck\Foundry\Persistence\flush_after;

/**
 * @testType functional
 */
final class CustomerFilterApiTest extends ApiTestCase
{
    use ApiClientHelperTrait;

    protected static ?bool $alwaysBootKernel = true;

    public function test_customers_can_be_filtered_by_name(): void
    {
        $client = $this->createAuthenticatedClient();

        flush_after(function () {
            CustomerFactory::createOne([
                'legalName' => 'Acme Industries',
                'name' => NameFactory::new(['firstName' => 'Alice', 'lastName' => 'Wonder']),
            ]); // ✔️ legal name
            CustomerFactory::createOne([
                'legalName' => 'Beta Labs',
                'name' => NameFactory::new(['firstName' => 'Bob', 'lastName' => 'Acme']),
            ]); // ✔️ last name
            CustomerFactory::createOne([
                'legalName' => 'Gamma Studio',
                'name' => NameFactory::new(['firstName' => 'Charlie', 'lastName' => 'Brown']),
            ]); // ❌
        });

        $response = $this->apiRequest($client, 'GET', '/api/customers?name=ac');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(2, $data['member']);

        foreach ($data['member'] as $customer) {
            $haystacks = [
                strtolower((string) ($customer['legalName'] ?? '')),
                strtolower((string) $customer['firstName']),
                strtolower((string) $customer['lastName']),
            ];

            static::assertTrue(
                array_reduce(
                    $haystacks,
                    static fn (bool $carry, string $value): bool => $carry || str_contains($value, 'ac'),
                    false
                )
            );
        }
    }

    public function test_customers_can_be_filtered_by_archived_flag(): void
    {
        $client = $this->createAuthenticatedClient();

        flush_after(function () {
            CustomerFactory::createMany(2, ['isArchived' => false]); // ❌
            CustomerFactory::createOne(['isArchived' => true]); // ✔️
        });

        $response = $this->apiRequest($client, 'GET', '/api/customers?archived=true');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(1, $data['member']);
        static::assertTrue($data['member'][0]['archived']);
    }

    public function test_customers_can_be_filtered_by_created_at(): void
    {
        $client = $this->createAuthenticatedClient();

        CustomerFactory::createSequence([
            ['createdAt' => new \DateTimeImmutable('2024-12-31')], // ❌
            ['createdAt' => new \DateTimeImmutable('2025-01-01')], // ✔️
            ['createdAt' => new \DateTimeImmutable('2025-06-01')], // ✔️
            ['createdAt' => new \DateTimeImmutable('2025-12-31')], // ✔️
            ['createdAt' => new \DateTimeImmutable('2026-01-01')], // ❌
        ]);

        $response = $this->apiRequest($client, 'GET', '/api/customers?createdAt[after]=2025-01-01&createdAt[before]=2025-12-31');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(3, $data['member']);

        $start = new \DateTimeImmutable('2025-01-01')->setTime(0, 0);
        $end = new \DateTimeImmutable('2025-12-31')->setTime(23, 59, 59);

        foreach ($data['member'] as $customer) {
            $createdAt = new \DateTimeImmutable($customer['createdAt']);
            static::assertGreaterThanOrEqual($start, $createdAt);
            static::assertLessThanOrEqual($end, $createdAt);
        }
    }
}
