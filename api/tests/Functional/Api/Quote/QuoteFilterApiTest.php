<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Tests\Functional\Api\Quote;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Quote\Quote;
use App\Domain\Enum\QuoteStatus;
use App\Domain\ValueObject\AmountBreakdown;
use App\Tests\Factory\Customer\CustomerFactory;
use App\Tests\Factory\Document\Quote\QuoteFactory;
use App\Tests\Factory\ValueObject\NameFactory;
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

    public function test_quotes_can_be_filtered_by_customer(): void
    {
        $client = $this->createAuthenticatedClient();

        $expectedQuotes = flush_after(function () {
            QuoteFactory::createOne([
                'customer' => CustomerFactory::createOne([
                    'legalName' => 'Hopper Industries',
                    'name' => NameFactory::new(['firstName' => 'Grace', 'lastName' => 'Hopper']),
                ]),
            ]); // ❌

            return [
                QuoteFactory::createOne([
                    'customer' => CustomerFactory::createOne([
                        'legalName' => 'Ada Consulting',
                        'name' => NameFactory::new(['firstName' => 'Nora', 'lastName' => 'Jones']),
                    ]),
                ]), // ✔️ legal name
                QuoteFactory::createOne([
                    'customer' => CustomerFactory::createOne([
                        'legalName' => 'Pixel Studio',
                        'name' => NameFactory::new(['firstName' => 'Ada', 'lastName' => 'Smith']),
                    ]),
                ]), // ✔️ first name
                QuoteFactory::createOne([
                    'customer' => CustomerFactory::createOne([
                        'name' => NameFactory::new(['lastName' => 'Adam']),
                    ]),
                ]), // ✔️ last name
            ];
        });

        $response = $this->apiRequest($client, 'GET', '/api/quotes?customer=ada');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(3, $data['member']);

        $expectedQuoteIds = array_map(fn (Quote $expectedQuote) => $expectedQuote->id->toRfc4122(), $expectedQuotes);

        foreach ($data['member'] as $quote) {
            static::assertContains(
                $quote['quoteId'],
                $expectedQuoteIds,
                'Returned quote should match the search term on customer identity.'
            );
        }
    }

    public function test_quotes_can_be_filtered_by_reference(): void
    {
        $client = $this->createAuthenticatedClient();

        flush_after(function () {
            QuoteFactory::createOne(['reference' => 'Q-2024-0001']); // ❌
            QuoteFactory::createOne(['reference' => 'Q-2025-0001']); // ✔️
            QuoteFactory::createOne(['reference' => 'Q-2025-0005']); // ✔️
            QuoteFactory::createOne(['reference' => 'Q-2026-0001']); // ❌
        });

        $response = $this->apiRequest($client, 'GET', '/api/quotes?reference=2025');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(2, $data['member']);

        foreach ($data['member'] as $quote) {
            static::assertStringContainsString('2025', $quote['reference']);
        }
    }

    public function test_quotes_can_be_filtered_by_title_or_subtitle(): void
    {
        $client = $this->createAuthenticatedClient();

        flush_after(function () {
            QuoteFactory::createOne(['title' => 'Project Kickoff', 'subtitle' => 'Alpha phase']); // ✔️
            QuoteFactory::createOne(['title' => 'Website redesign', 'subtitle' => 'Project Discovery']); // ✔️
            QuoteFactory::createOne(['title' => 'Maintenance plan', 'subtitle' => 'Support']); // ❌
        });

        $response = $this->apiRequest($client, 'GET', '/api/quotes?title=project');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(2, $data['member']);

        foreach ($data['member'] as $quote) {
            $title = strtolower((string) $quote['title']);
            $subtitle = strtolower((string) ($quote['subtitle'] ?? ''));
            static::assertTrue(str_contains($title, 'project') || str_contains($subtitle, 'project'));
        }
    }

    public function test_quotes_can_be_filtered_by_total_net_range(): void
    {
        $client = $this->createAuthenticatedClient();

        flush_after(function () {
            QuoteFactory::createOne(['total' => AmountBreakdown::fromValues('400.00', '80.00', '480.00')]); // ❌
            QuoteFactory::createOne(['total' => AmountBreakdown::fromValues('600.00', '120.00', '720.00')]); // ✔️
            QuoteFactory::createOne(['total' => AmountBreakdown::fromValues('900.00', '180.00', '1080.00')]); // ✔️
            QuoteFactory::createOne(['total' => AmountBreakdown::fromValues('1500.00', '300.00', '1800.00')]); // ❌
        });

        $response = $this->apiRequest($client, 'GET', '/api/quotes?totalNet[gte]=600&totalNet[lte]=1000');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(2, $data['member']);

        foreach ($data['member'] as $quote) {
            $net = (float) $quote['total']['net'];
            static::assertGreaterThanOrEqual(600.0, $net);
            static::assertLessThanOrEqual(1000.0, $net);
        }
    }

    public function test_quotes_can_be_filtered_by_total_gross_range(): void
    {
        $client = $this->createAuthenticatedClient();

        flush_after(function () {
            QuoteFactory::createOne(['total' => AmountBreakdown::fromValues('300.00', '60.00', '360.00')]); // ❌
            QuoteFactory::createOne(['total' => AmountBreakdown::fromValues('700.00', '140.00', '840.00')]); // ✔️
            QuoteFactory::createOne(['total' => AmountBreakdown::fromValues('900.00', '180.00', '1080.00')]); // ✔️
            QuoteFactory::createOne(['total' => AmountBreakdown::fromValues('1200.00', '240.00', '1440.00')]); // ❌
        });

        $response = $this->apiRequest($client, 'GET', '/api/quotes?totalGross[gt]=800&totalGross[lt]=1200');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(2, $data['member']);

        foreach ($data['member'] as $quote) {
            $gross = (float) $quote['total']['gross'];
            static::assertGreaterThan(800.0, $gross);
            static::assertLessThan(1200.0, $gross);
        }
    }

    public function test_quotes_can_be_filtered_by_archived_flag(): void
    {
        $client = $this->createAuthenticatedClient();

        flush_after(function () {
            QuoteFactory::createMany(2, ['isArchived' => false]); // ❌
            QuoteFactory::createOne(['isArchived' => true]); // ✔️
        });

        $response = $this->apiRequest($client, 'GET', '/api/quotes?archived=true');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(1, $data['member']);
        static::assertTrue($data['member'][0]['archived']);
    }
}
