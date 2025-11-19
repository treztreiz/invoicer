<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Enum\QuoteStatus;
use App\Tests\Factory\Customer\CustomerFactory;
use App\Tests\Factory\Document\QuoteFactory;
use App\Tests\Functional\Api\Common\ApiClientHelperTrait;

use function Zenstruck\Foundry\Persistence\flush_after;

/**
 * @testType functional
 */
final class QuoteFilterApiTest extends ApiTestCase
{
    use ApiClientHelperTrait;

    protected static ?bool $alwaysBootKernel = true;

    public function test_quotes_can_be_filtered_by_status(): void
    {
        $client = $this->createAuthenticatedClient();

        flush_after(function () {
            QuoteFactory::new()->draft()->many(2)->create(); // ❌
            QuoteFactory::new()->sent()->many(3)->create(); // ✔️️
            QuoteFactory::new()->accepted()->many(4)->create(); // ✔️️
        });

        $response = $this->apiRequest($client, 'GET', '/api/quotes?status[]=SENT&status[]=ACCEPTED');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(7, $data['member']);

        foreach ($data['member'] as $quote) {
            static::assertContains($quote['status'], [QuoteStatus::SENT->value, QuoteStatus::ACCEPTED->value]);
        }
    }

    public function test_quotes_can_be_filtered_by_customer_id(): void
    {
        $client = $this->createAuthenticatedClient();

        /** @var Customer $customer */
        $customer = flush_after(function () {
            $customer = CustomerFactory::createOne();
            QuoteFactory::createMany(2, ['customer' => $customer]); // ✔️️
            QuoteFactory::createMany(3); // ❌

            return $customer;
        });

        $customerId = $customer->id->toRfc4122();

        $response = $this->apiRequest($client, 'GET', sprintf('/api/quotes?customerId=%s', $customerId));

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(2, $data['member']);

        static::assertSame($customerId, $data['member'][0]['customerId']);
        static::assertSame($customerId, $data['member'][1]['customerId']);
    }

    public function test_quotes_can_be_filtered_by_created_at(): void
    {
        $client = $this->createAuthenticatedClient();

        QuoteFactory::createSequence([
            ['createdAt' => new \DateTimeImmutable('2024-12-31')], // ❌
            ['createdAt' => new \DateTimeImmutable('2025-01-01')], // ✔️️
            ['createdAt' => new \DateTimeImmutable('2025-03-01')], // ✔️️
            ['createdAt' => new \DateTimeImmutable('2025-04-13')], // ✔️️
            ['createdAt' => new \DateTimeImmutable('2025-12-31')], // ✔️️
            ['createdAt' => new \DateTimeImmutable('2026-01-01')], // ❌
        ]);

        $response = $this->apiRequest($client, 'GET', '/api/quotes?createdAt[after]=2025-01-01&createdAt[before]=2025-12-31');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(4, $data['member']);

        $startDate = new \DateTimeImmutable('2025-01-01')->setTime(0, 0);
        $endDate = new \DateTimeImmutable('2025-12-31')->setTime(23, 59, 59);

        foreach ($data['member'] as $quote) {
            static::assertGreaterThanOrEqual($startDate, new \DateTimeImmutable($quote['createdAt']));
            static::assertLessThanOrEqual($endDate, new \DateTimeImmutable($quote['createdAt']));
        }
    }
}
