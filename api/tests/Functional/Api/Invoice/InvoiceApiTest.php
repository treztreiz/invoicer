<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Tests\Functional\Api\Invoice;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Enum\InvoiceStatus;
use App\Tests\Factory\Customer\CustomerFactory;
use App\Tests\Factory\Document\InvoiceFactory;
use App\Tests\Functional\Api\Common\ApiClientHelperTrait;
use Symfony\Component\Uid\Uuid;

final class BaseInvoiceApiTest extends ApiTestCase
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

    public function test_issue_invoice_transitions_to_issued(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::createOne(['status' => InvoiceStatus::DRAFT]);

        $response = $this->apiRequest($client, 'POST', sprintf('/api/invoices/%s/actions', $invoice->id->toRfc4122()), [
            'json' => ['action' => 'issue'],
        ]);

        self::assertResponseIsSuccessful();
        static::assertSame('ISSUED', $response->toArray(false)['status']);
        static::assertSame(InvoiceStatus::ISSUED, $invoice->status);
    }

    public function test_mark_paid_transitions_to_paid(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::createOne(['status' => InvoiceStatus::ISSUED]);

        $response = $this->apiRequest($client, 'POST', sprintf('/api/invoices/%s/actions', $invoice->id->toRfc4122()), [
            'json' => ['action' => 'mark_paid'],
        ]);

        self::assertResponseIsSuccessful();
        static::assertSame('PAID', $response->toArray(false)['status']);
        static::assertSame(InvoiceStatus::PAID, $invoice->status);
    }

    public function test_update_invoice_mutates_document(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::createOne(['status' => InvoiceStatus::DRAFT]);

        $response = $this->apiRequest($client, 'PUT', sprintf('/api/invoices/%s', $invoice->id->toRfc4122()), [
            'json' => $this->invoicePayload(
                CustomerFactory::createOne()->id->toRfc4122(),
                [
                    'title' => 'Updated invoice',
                    'currency' => 'USD',
                    'dueDate' => new \DateTimeImmutable('+2 weeks')->format('Y-m-d'),
                    'lines' => [['description' => 'Consulting']],
                ]
            ),
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);
        static::assertSame('Updated invoice', $data['title']);
        static::assertCount(1, $data['lines']);

        static::assertSame('Updated invoice', $invoice->title);
        static::assertSame('USD', $invoice->currency);
        static::assertCount(1, $invoice->lines);
    }

    public function test_update_invoice_rejected_when_not_draft(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::createOne(['status' => InvoiceStatus::ISSUED]);

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
