<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Enum\QuoteStatus;
use App\Tests\Factory\Customer\CustomerFactory;
use App\Tests\Factory\Document\QuoteFactory;
use App\Tests\Functional\Api\Common\ApiClientHelperTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Uuid;

final class QuoteApiTest extends ApiTestCase
{
    use ApiClientHelperTrait;

    protected static ?bool $alwaysBootKernel = true;

    public function test_create_quote_persists_document(): void
    {
        $client = $this->createAuthenticatedClient();

        $response = $this->apiRequest($client, 'POST', '/api/quotes', [
            'json' => $this->createQuotePayload(CustomerFactory::createOne()->id->toRfc4122()),
        ]);

        self::assertResponseStatusCodeSame(201);
        $data = $response->toArray(false);

        static::assertSame('Website revamp', $data['title']);
        static::assertSame('2400.00', $data['total']['gross']);
        static::assertSame(QuoteStatus::DRAFT->value, $data['status']);
        static::assertSame(['send'], $data['availableActions']);

        QuoteFactory::assert()->exists([
            'id' => Uuid::fromString($data['quoteId']),
            'title' => 'Website revamp',
            'status' => QuoteStatus::DRAFT,
        ]);
    }

    public function test_list_quotes_returns_collection(): void
    {
        $client = $this->createAuthenticatedClient();

        QuoteFactory::createMany(10);

        $response = $this->apiRequest($client, 'GET', '/api/quotes');

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertNotEmpty($data);
        static::assertCount(10, $data['member']);
        static::assertNotEmpty($data['member'][0]['availableActions'] ?? []);

        QuoteFactory::assert()->count(10);
    }

    public function test_send_quote_transitions_to_sent(): void
    {
        $client = $this->createAuthenticatedClient();

        $quote = QuoteFactory::new()->draft()->create();
        static::assertSame(QuoteStatus::DRAFT, $quote->status);

        $response = $this->apiRequest($client, 'POST', sprintf('/api/quotes/%s/actions', $quote->id->toRfc4122()), [
            'json' => ['action' => 'send'],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame(QuoteStatus::SENT->value, $data['status']);
        static::assertSame(['accept', 'reject'], $data['availableActions']);

        static::assertSame(QuoteStatus::SENT, $quote->status);
    }

    #[DataProvider('quoteTransitionProvider')]
    public function test_sent_quote_can_transition_to_accepted_or_rejected(string $transition, QuoteStatus $status): void
    {
        $client = $this->createAuthenticatedClient();

        $quote = QuoteFactory::new()->sent()->create();
        static::assertSame(QuoteStatus::SENT, $quote->status);

        $response = $this->apiRequest($client, 'POST', sprintf('/api/quotes/%s/actions', $quote->id->toRfc4122()), [
            'json' => ['action' => $transition],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame($status->value, $data['status']);
        static::assertSame([], $data['availableActions']);

        static::assertSame($status, $quote->status);
    }

    public static function quoteTransitionProvider(): iterable
    {
        yield 'accept' => ['accept', QuoteStatus::ACCEPTED];
        yield 'reject' => ['reject', QuoteStatus::REJECTED];
    }

    public function test_update_quote_mutates_document(): void
    {
        $client = $this->createAuthenticatedClient();

        $quote = QuoteFactory::createOne([
            'status' => QuoteStatus::DRAFT,
            'title' => 'New quote',
            'subtitle' => 'New scope',
            'currency' => 'EUR',
        ]);

        static::assertSame('New quote', $quote->title);
        static::assertSame('New scope', $quote->subtitle);
        static::assertSame('EUR', $quote->currency);
        static::assertCount(0, $quote->lines);

        $response = $this->apiRequest($client, 'PUT', sprintf('/api/quotes/%s', $quote->id->toRfc4122()), [
            'json' => $this->createQuotePayload(
                CustomerFactory::createOne()->id->toRfc4122(),
                [
                    'title' => 'Updated quote',
                    'subtitle' => 'Updated scope',
                    'currency' => 'USD',
                    'lines' => [['description' => 'Consulting']],
                ]
            ),
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame('Updated quote', $data['title']);
        static::assertSame('Updated scope', $data['subtitle']);
        static::assertSame('USD', $data['currency']);
        static::assertCount(1, $data['lines']);

        static::assertSame('Updated quote', $quote->title);
        static::assertSame('Updated scope', $quote->subtitle);
        static::assertSame('USD', $quote->currency);
        static::assertCount(1, $quote->lines);
    }

    public function test_update_quote_rejected_when_not_draft(): void
    {
        $client = $this->createAuthenticatedClient();

        $quote = QuoteFactory::new()->sent()->create();

        $this->apiRequest($client, 'PUT', sprintf('/api/quotes/%s', $quote->id->toRfc4122()), [
            'json' => $this->createQuotePayload(CustomerFactory::createOne()->id->toRfc4122()),
        ]);

        self::assertResponseStatusCodeSame(400);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function createQuotePayload(string $customerId, array $override = []): array
    {
        $payload = [
            'title' => 'Website revamp',
            'subtitle' => 'Phase 1',
            'currency' => 'EUR',
            'vatRate' => 20,
            'customerId' => $customerId,
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

        if (array_key_exists('lines', $override)) {
            $payload['lines'] = $override['lines'];
            unset($override['lines']);
        }

        return array_replace($payload, $override);
    }
}
