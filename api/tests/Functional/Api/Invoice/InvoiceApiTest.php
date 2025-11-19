<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Tests\Functional\Api\Invoice;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Entity\Document\DocumentLine;
use App\Domain\Enum\InvoiceStatus;
use App\Tests\Factory\Customer\CustomerFactory;
use App\Tests\Factory\Document\DocumentLineFactory;
use App\Tests\Factory\Document\InvoiceFactory;
use App\Tests\Functional\Api\Common\ApiClientHelperTrait;
use Symfony\Component\Uid\Uuid;

/**
 * @testType functional
 */
final class InvoiceApiTest extends ApiTestCase
{
    use ApiClientHelperTrait;

    protected static ?bool $alwaysBootKernel = true;

    public function test_create_invoice_persists_document(): void
    {
        $client = $this->createAuthenticatedClient();

        $response = $this->apiRequest($client, 'POST', '/api/invoices', [
            'json' => $this->invoicePayload(CustomerFactory::createOne()->id->toRfc4122()),
        ]);

        self::assertResponseStatusCodeSame(201);
        $data = $response->toArray(false);

        static::assertSame('Website revamp', $data['title']);
        static::assertSame('2400.00', $data['total']['gross']);
        static::assertSame('DRAFT', $data['status']);

        InvoiceFactory::assert()->exists([
            'id' => Uuid::fromString($data['invoiceId']),
            'status' => InvoiceStatus::DRAFT,
        ]);
    }

    public function test_list_invoices_returns_collection(): void
    {
        $client = $this->createAuthenticatedClient();
        InvoiceFactory::createMany(10);

        $response = $this->apiRequest($client, 'GET', '/api/invoices');

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertCount(10, $data['member']);
    }

    public function test_get_invoice_returns_single_invoice(): void
    {
        $client = $this->createAuthenticatedClient();

        $invoice = InvoiceFactory::createOne(['title' => 'New invoice']);

        $response = $this->apiRequest($client, 'GET', sprintf('/api/invoices/%s', $invoice->id->toRfc4122()));

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame('New invoice', $data['title']);
    }

    public function test_issue_invoice_transitions_to_issued(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::new()->draft()->create();

        $response = $this->apiRequest($client, 'POST', sprintf('/api/invoices/%s/transition', $invoice->id->toRfc4122()), [
            'json' => ['transition' => 'issue'],
        ]);

        self::assertResponseIsSuccessful();
        static::assertSame('ISSUED', $response->toArray(false)['status']);
        static::assertSame(InvoiceStatus::ISSUED, $invoice->status);
    }

    public function test_mark_paid_transitions_to_paid(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::new()->issued()->create();

        $response = $this->apiRequest($client, 'POST', sprintf('/api/invoices/%s/transition', $invoice->id->toRfc4122()), [
            'json' => ['transition' => 'mark_paid'],
        ]);

        self::assertResponseIsSuccessful();
        static::assertSame('PAID', $response->toArray(false)['status']);
        static::assertSame(InvoiceStatus::PAID, $invoice->status);
    }

    public function test_update_invoice_mutates_document(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::new()->draft()->withLines(3)->create();
        $firstLine = $invoice->lines->first();

        static::assertInstanceOf(DocumentLine::class, $firstLine);
        static::assertSame(0, $firstLine->position);
        DocumentLineFactory::assert()->count(3);

        $response = $this->apiRequest($client, 'PUT', sprintf('/api/invoices/%s', $invoice->id->toRfc4122()), [
            'json' => $this->invoicePayload(
                CustomerFactory::createOne()->id->toRfc4122(),
                [
                    'title' => 'Updated invoice',
                    'currency' => 'USD',
                    'lines' => [
                        [
                            'description' => 'Consulting',
                            'quantity' => 2,
                            'rateUnit' => 'DAILY',
                            'rate' => 600,
                        ],
                        [
                            'lineId' => $firstLine->id->toRfc4122(),
                            'description' => 'Marketing',
                            'quantity' => 10,
                            'rateUnit' => 'HOURLY',
                            'rate' => 80,
                        ],
                    ],
                ]
            ),
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);
        static::assertSame('Updated invoice', $data['title']);
        static::assertCount(2, $data['lines']);

        static::assertSame('Updated invoice', $invoice->title);
        static::assertSame('USD', $invoice->currency);
        static::assertCount(2, $invoice->lines);

        // Ensure first line has been mutated (renamed and repositioned)
        static::assertSame('Marketing', $firstLine->description);
        static::assertSame(1, $firstLine->position);
        // Ensure previous lines have been deleted
        DocumentLineFactory::assert()->count(2);
    }

    public function test_update_invoice_rejected_when_not_draft(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::new()->issued()->create();

        $response = $this->apiRequest($client, 'PUT', sprintf('/api/invoices/%s', $invoice->id->toRfc4122()), [
            'json' => $this->invoicePayload(CustomerFactory::createOne()->id->toRfc4122()),
        ]);

        self::assertResponseStatusCodeSame(400);
        static::assertSame('Only draft invoices can be updated.', $response->toArray(false)['detail'] ?? null);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function invoicePayload(string $customerId, array $override = []): array
    {
        $payload = [
            'title' => 'Website revamp',
            'subtitle' => 'Phase 1',
            'currency' => 'EUR',
            'vatRate' => 20,
            'customerId' => $customerId,
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

        if (array_key_exists('lines', $override)) {
            $payload['lines'] = $override['lines'];
            unset($override['lines']);
        }

        return array_replace($payload, $override);
    }
}
