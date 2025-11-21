<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Tests\Functional\Api\Invoice;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Invoice\Invoice;
use App\Domain\Enum\InvoiceStatus;
use App\Domain\ValueObject\AmountBreakdown;
use App\Tests\Factory\Customer\CustomerFactory;
use App\Tests\Factory\Document\Invoice\InvoiceFactory;
use App\Tests\Factory\ValueObject\NameFactory;
use App\Tests\Functional\Api\Common\ApiClientHelperTrait;
use Symfony\Component\Uid\Uuid;

use function Zenstruck\Foundry\Persistence\flush_after;

/**
 * @testType functional
 */
final class InvoiceFilterApiTest extends ApiTestCase
{
    use ApiClientHelperTrait;

    protected static ?bool $alwaysBootKernel = true;

    public function test_invoices_can_be_filtered_by_status(): void
    {
        $client = $this->createAuthenticatedClient();

        flush_after(function () {
            InvoiceFactory::new()->draft()->many(2)->create(); // ❌
            InvoiceFactory::new()->issued()->many(3)->create(); // ✔️️
            InvoiceFactory::new()->overdue()->many(3)->create(); // ✔️️
            InvoiceFactory::new()->paid()->many(4)->create(); // ✔️️
            InvoiceFactory::new()->voided()->many(2)->create(); // ✔️️
        });

        $response = $this->apiRequest($client, 'GET', '/api/invoices?status[]=ISSUED&status[]=OVERDUE&status[]=PAID');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(10, $data['member']);

        foreach ($data['member'] as $invoice) {
            static::assertContains($invoice['status'], [InvoiceStatus::ISSUED->value, InvoiceStatus::OVERDUE->value, InvoiceStatus::PAID->value]);
        }
    }

    public function test_invoices_can_be_filtered_by_created_at(): void
    {
        $client = $this->createAuthenticatedClient();

        InvoiceFactory::createSequence([
            ['createdAt' => new \DateTimeImmutable('2024-12-31')], // ❌
            ['createdAt' => new \DateTimeImmutable('2025-01-01')], // ✔️️
            ['createdAt' => new \DateTimeImmutable('2025-03-01')], // ✔️️
            ['createdAt' => new \DateTimeImmutable('2025-04-13')], // ✔️️
            ['createdAt' => new \DateTimeImmutable('2025-12-31')], // ✔️️
            ['createdAt' => new \DateTimeImmutable('2026-01-01')], // ❌
        ]);

        $response = $this->apiRequest($client, 'GET', '/api/invoices?createdAt[after]=2025-01-01&createdAt[before]=2025-12-31');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(4, $data['member']);

        $startDate = new \DateTimeImmutable('2025-01-01')->setTime(0, 0);
        $endDate = new \DateTimeImmutable('2025-12-31')->setTime(23, 59, 59);

        foreach ($data['member'] as $invoice) {
            static::assertGreaterThanOrEqual($startDate, new \DateTimeImmutable($invoice['createdAt']));
            static::assertLessThanOrEqual($endDate, new \DateTimeImmutable($invoice['createdAt']));
        }
    }

    public function test_invoices_can_be_filtered_by_customer_id(): void
    {
        $client = $this->createAuthenticatedClient();

        /** @var Customer $customer */
        $customer = flush_after(function () {
            $customer = CustomerFactory::createOne();
            InvoiceFactory::createMany(2, ['customer' => $customer]); // ✔️️
            InvoiceFactory::createMany(3); // ❌

            return $customer;
        });

        $customerId = $customer->id->toRfc4122();

        $response = $this->apiRequest($client, 'GET', sprintf('/api/invoices?customerId=%s', $customerId));

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(2, $data['member']);

        static::assertSame($customerId, $data['member'][0]['customerId']);
        static::assertSame($customerId, $data['member'][1]['customerId']);
    }

    public function test_invoices_can_be_filtered_by_customer(): void
    {
        $client = $this->createAuthenticatedClient();

        $expectedInvoices = flush_after(function () {
            InvoiceFactory::createOne([
                'customer' => CustomerFactory::createOne([
                    'legalName' => 'Hopper Industries',
                    'name' => NameFactory::new(['firstName' => 'Grace', 'lastName' => 'Hopper']),
                ]),
            ]); // ❌

            return [
                InvoiceFactory::createOne([
                    'customer' => CustomerFactory::createOne([
                        'legalName' => 'Ada Consulting',
                        'name' => NameFactory::new(['firstName' => 'Nora', 'lastName' => 'Jones']),
                    ]),
                ]), // ✔️ legal name
                InvoiceFactory::createOne([
                    'customer' => CustomerFactory::createOne([
                        'legalName' => 'Pixel Studio',
                        'name' => NameFactory::new(['firstName' => 'Ada', 'lastName' => 'Smith']),
                    ]),
                ]), // ✔️ first name
                InvoiceFactory::createOne([
                    'customer' => CustomerFactory::createOne([
                        'name' => NameFactory::new(['lastName' => 'Adam']),
                    ]),
                ]), // ✔️ last name
            ];
        });

        $response = $this->apiRequest($client, 'GET', '/api/invoices?customer=ada');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(3, $data['member']);

        $expectedInvoiceIds = array_map(fn (Invoice $expectedInvoice) => $expectedInvoice->id->toRfc4122(), $expectedInvoices);

        foreach ($data['member'] as $invoice) {
            static::assertContains(
                $invoice['invoiceId'],
                $expectedInvoiceIds,
                'Returned invoice should match the search term on customer identity.'
            );
        }
    }

    public function test_invoices_can_be_filtered_by_reference(): void
    {
        $client = $this->createAuthenticatedClient();

        flush_after(function () {
            InvoiceFactory::createOne(['reference' => 'INV-2024-0001']); // ❌
            InvoiceFactory::createOne(['reference' => 'INV-2025-0001']); // ✔️
            InvoiceFactory::createOne(['reference' => 'INV-2025-0005']); // ✔️
            InvoiceFactory::createOne(['reference' => 'INV-2026-0001']); // ❌
        });

        $response = $this->apiRequest($client, 'GET', '/api/invoices?reference=2025');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(2, $data['member']);

        foreach ($data['member'] as $invoice) {
            static::assertStringContainsString('2025', $invoice['reference']);
        }
    }

    public function test_invoices_can_be_filtered_by_title_or_subtitle(): void
    {
        $client = $this->createAuthenticatedClient();

        flush_after(function () {
            InvoiceFactory::createOne(['title' => 'Project Kickoff', 'subtitle' => 'Alpha phase']); // ✔️
            InvoiceFactory::createOne(['title' => 'Website redesign', 'subtitle' => 'Project Discovery']); // ✔️
            InvoiceFactory::createOne(['title' => 'Maintenance plan', 'subtitle' => 'Support']); // ❌
        });

        $response = $this->apiRequest($client, 'GET', '/api/invoices?title=project');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(2, $data['member']);

        foreach ($data['member'] as $invoice) {
            $title = strtolower((string) $invoice['title']);
            $subtitle = strtolower((string) ($invoice['subtitle'] ?? ''));
            static::assertTrue(str_contains($title, 'project') || str_contains($subtitle, 'project'));
        }
    }

    public function test_invoices_can_be_filtered_by_total_net_range(): void
    {
        $client = $this->createAuthenticatedClient();

        flush_after(function () {
            InvoiceFactory::createOne(['total' => AmountBreakdown::fromValues('400.00', '80.00', '480.00')]); // ❌
            InvoiceFactory::createOne(['total' => AmountBreakdown::fromValues('600.00', '120.00', '720.00')]); // ✔️
            InvoiceFactory::createOne(['total' => AmountBreakdown::fromValues('900.00', '180.00', '1080.00')]); // ✔️
            InvoiceFactory::createOne(['total' => AmountBreakdown::fromValues('1500.00', '300.00', '1800.00')]); // ❌
        });

        $response = $this->apiRequest($client, 'GET', '/api/invoices?totalNet[gte]=600&totalNet[lte]=1000');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(2, $data['member']);

        foreach ($data['member'] as $invoice) {
            $net = (float) $invoice['total']['net'];
            static::assertGreaterThanOrEqual(600.0, $net);
            static::assertLessThanOrEqual(1000.0, $net);
        }
    }

    public function test_invoices_can_be_filtered_by_total_gross_range(): void
    {
        $client = $this->createAuthenticatedClient();

        flush_after(function () {
            InvoiceFactory::createOne(['total' => AmountBreakdown::fromValues('300.00', '60.00', '360.00')]); // ❌
            InvoiceFactory::createOne(['total' => AmountBreakdown::fromValues('700.00', '140.00', '840.00')]); // ✔️
            InvoiceFactory::createOne(['total' => AmountBreakdown::fromValues('900.00', '180.00', '1080.00')]); // ✔️
            InvoiceFactory::createOne(['total' => AmountBreakdown::fromValues('1200.00', '240.00', '1440.00')]); // ❌
        });

        $response = $this->apiRequest($client, 'GET', '/api/invoices?totalGross[gt]=800&totalGross[lt]=1200');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(2, $data['member']);

        foreach ($data['member'] as $invoice) {
            $gross = (float) $invoice['total']['gross'];
            static::assertGreaterThan(800.0, $gross);
            static::assertLessThan(1200.0, $gross);
        }
    }

    public function test_invoices_can_be_filtered_by_archived_flag(): void
    {
        $client = $this->createAuthenticatedClient();

        flush_after(function () {
            InvoiceFactory::createMany(2, ['isArchived' => false]); // ❌
            InvoiceFactory::createOne(['isArchived' => true]); // ✔️
        });

        $response = $this->apiRequest($client, 'GET', '/api/invoices?archived=true');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(1, $data['member']);
        static::assertTrue($data['member'][0]['archived']);
    }

    public function test_invoices_can_be_filtered_by_issued_at(): void
    {
        $client = $this->createAuthenticatedClient();

        InvoiceFactory::createSequence([
            ['issuedAt' => new \DateTimeImmutable('2024-12-31')], // ❌
            ['issuedAt' => new \DateTimeImmutable('2025-01-01')], // ✔️
            ['issuedAt' => new \DateTimeImmutable('2025-06-15')], // ✔️
            ['issuedAt' => new \DateTimeImmutable('2025-12-31')], // ✔️
            ['issuedAt' => new \DateTimeImmutable('2026-01-01')], // ❌
        ]);

        $response = $this->apiRequest($client, 'GET', '/api/invoices?issuedAt[after]=2025-01-01&issuedAt[before]=2025-12-31');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(3, $data['member']);

        $startDate = new \DateTimeImmutable('2025-01-01')->setTime(0, 0);
        $endDate = new \DateTimeImmutable('2025-12-31')->setTime(23, 59, 59);

        foreach ($data['member'] as $invoice) {
            static::assertNotNull($invoice['issuedAt']);
            $issuedAt = new \DateTimeImmutable($invoice['issuedAt']);
            static::assertGreaterThanOrEqual($startDate, $issuedAt);
            static::assertLessThanOrEqual($endDate, $issuedAt);
        }
    }

    public function test_invoices_can_be_filtered_by_due_date(): void
    {
        $client = $this->createAuthenticatedClient();

        InvoiceFactory::createSequence([
            ['dueDate' => new \DateTimeImmutable('2024-12-31')], // ❌
            ['dueDate' => new \DateTimeImmutable('2025-02-01')], // ✔️
            ['dueDate' => new \DateTimeImmutable('2025-05-01')], // ✔️
            ['dueDate' => new \DateTimeImmutable('2025-11-01')], // ✔️
            ['dueDate' => new \DateTimeImmutable('2026-01-01')], // ❌
        ]);

        $response = $this->apiRequest($client, 'GET', '/api/invoices?dueDate[after]=2025-01-01&dueDate[before]=2025-12-31');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(3, $data['member']);

        $startDate = new \DateTimeImmutable('2025-01-01');
        $endDate = new \DateTimeImmutable('2025-12-31');

        foreach ($data['member'] as $invoice) {
            static::assertNotNull($invoice['dueDate']);
            $dueDate = new \DateTimeImmutable($invoice['dueDate']);
            static::assertGreaterThanOrEqual($startDate, $dueDate);
            static::assertLessThanOrEqual($endDate, $dueDate);
        }
    }

    public function test_invoices_can_be_filtered_by_paid_at(): void
    {
        $client = $this->createAuthenticatedClient();

        InvoiceFactory::createSequence([
            ['paidAt' => new \DateTimeImmutable('2024-12-31')], // ❌
            ['paidAt' => new \DateTimeImmutable('2025-02-15')], // ✔️
            ['paidAt' => new \DateTimeImmutable('2025-07-20')], // ✔️
            ['paidAt' => new \DateTimeImmutable('2025-11-05')], // ✔️
            ['paidAt' => new \DateTimeImmutable('2026-01-10')], // ❌
        ]);

        $response = $this->apiRequest($client, 'GET', '/api/invoices?paidAt[after]=2025-01-01&paidAt[before]=2025-12-31');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(3, $data['member']);

        $startDate = new \DateTimeImmutable('2025-01-01')->setTime(0, 0);
        $endDate = new \DateTimeImmutable('2025-12-31')->setTime(23, 59, 59);

        foreach ($data['member'] as $invoice) {
            static::assertNotNull($invoice['paidAt']);
            $paidAt = new \DateTimeImmutable($invoice['paidAt']);
            static::assertGreaterThanOrEqual($startDate, $paidAt);
            static::assertLessThanOrEqual($endDate, $paidAt);
        }
    }

    public function test_invoices_can_be_filtered_by_recurrence_presence(): void
    {
        $client = $this->createAuthenticatedClient();

        $expectedInvoice = flush_after(function () {
            InvoiceFactory::createOne(); // ❌

            return InvoiceFactory::new()->withRecurrence()->create();
        });

        $response = $this->apiRequest($client, 'GET', '/api/invoices?recurrence=true');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(1, $data['member']);
        static::assertSame($expectedInvoice->id->toRfc4122(), $data['member'][0]['invoiceId']);
        static::assertNotNull($data['member'][0]['recurrence']);
    }

    public function test_invoices_can_be_filtered_by_installment_plan_presence(): void
    {
        $client = $this->createAuthenticatedClient();

        $expectedInvoice = flush_after(function () {
            InvoiceFactory::createOne(); // ❌

            return InvoiceFactory::new()->withInstallmentPlan(2)->create();
        });

        $response = $this->apiRequest($client, 'GET', '/api/invoices?installmentPlan=true');

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(1, $data['member']);
        static::assertSame($expectedInvoice->id->toRfc4122(), $data['member'][0]['invoiceId']);
        static::assertNotNull($data['member'][0]['installmentPlan']);
    }

    public function test_invoices_can_be_filtered_by_recurrence_seed_id(): void
    {
        $client = $this->createAuthenticatedClient();
        $seedId = Uuid::v7();
        $expectedInvoiceId = null;

        flush_after(function () use ($seedId, &$expectedInvoiceId) {
            $match = InvoiceFactory::createOne(['recurrenceSeedId' => $seedId]); // ✔️
            InvoiceFactory::createOne(['recurrenceSeedId' => Uuid::v7()]); // ❌
            InvoiceFactory::createOne(); // ❌
            $expectedInvoiceId = $match->id->toRfc4122();
        });

        $response = $this->apiRequest($client, 'GET', sprintf('/api/invoices?recurrenceSeedId=%s', $seedId->toRfc4122()));

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(1, $data['member']);
        static::assertSame($expectedInvoiceId, $data['member'][0]['invoiceId']);
    }

    public function test_invoices_can_be_filtered_by_installment_seed_id(): void
    {
        $client = $this->createAuthenticatedClient();
        $seedId = Uuid::v7();
        $expectedInvoiceId = null;

        flush_after(function () use ($seedId, &$expectedInvoiceId) {
            $match = InvoiceFactory::createOne(['installmentSeedId' => $seedId]); // ✔️
            InvoiceFactory::createOne(['installmentSeedId' => Uuid::v7()]); // ❌
            InvoiceFactory::createOne(); // ❌
            $expectedInvoiceId = $match->id->toRfc4122();
        });

        $response = $this->apiRequest($client, 'GET', sprintf('/api/invoices?installmentSeedId=%s', $seedId->toRfc4122()));

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(1, $data['member']);
        static::assertSame($expectedInvoiceId, $data['member'][0]['invoiceId']);
    }
}
