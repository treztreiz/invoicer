<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Tests\Functional\Api\Invoice;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Enum\RecurrenceFrequency;
use App\Tests\Factory\Document\Invoice\InstallmentPlanFactory;
use App\Tests\Factory\Document\Invoice\InvoiceRecurrenceFactory;
use App\Tests\Factory\Document\InvoiceFactory;
use App\Tests\Functional\Api\Common\ApiClientHelperTrait;

final class InvoiceRecurrenceApiTest extends ApiTestCase
{
    use ApiClientHelperTrait;

    protected static ?bool $alwaysBootKernel = true;

    public function test_attach_invoice_recurrence(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::createOne();

        static::assertNull($invoice->recurrence);

        $response = $this->apiRequest($client, 'POST', sprintf('/api/invoices/%s/recurrence', $invoice->id->toRfc4122()), [
            'json' => $this->recurrencePayload(),
        ]);

        self::assertResponseIsSuccessful();
        static::assertSame('MONTHLY', $response->toArray(false)['recurrence']['frequency']);
        static::assertNotNull($invoice->recurrence);
        static::assertSame(RecurrenceFrequency::MONTHLY, $invoice->recurrence->frequency);
    }

    public function test_update_invoice_recurrence(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::createOne([
            'recurrence' => InvoiceRecurrenceFactory::new([
                'frequency' => RecurrenceFrequency::MONTHLY,
            ]),
        ]);

        $response = $this->apiRequest($client, 'PUT', sprintf('/api/invoices/%s/recurrence', $invoice->id->toRfc4122()), [
            'json' => $this->recurrencePayload(['frequency' => 'QUARTERLY']),
        ]);

        self::assertResponseIsSuccessful();
        static::assertSame('QUARTERLY', $response->toArray(false)['recurrence']['frequency']);
        static::assertSame(RecurrenceFrequency::QUARTERLY, $invoice->recurrence?->frequency);
    }

    public function test_delete_invoice_recurrence(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::createOne([
            'recurrence' => InvoiceRecurrenceFactory::new(),
        ]);

        static::assertNotNull($invoice->recurrence);

        $response = $this->apiRequest($client, 'DELETE', sprintf('/api/invoices/%s/recurrence', $invoice->id->toRfc4122()));

        self::assertResponseIsSuccessful();
        static::assertNull($response->toArray(false)['recurrence']);
        static::assertNull($invoice->recurrence);
    }

    public function test_attach_recurrence_rejected_when_installment_plan_exists(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::createOne([
            'installmentPlan' => InstallmentPlanFactory::new(),
        ]);

        $response = $this->apiRequest($client, 'POST', sprintf('/api/invoices/%s/recurrence', $invoice->id->toRfc4122()), [
            'json' => $this->recurrencePayload(),
        ]);

        self::assertResponseStatusCodeSame(400);
        static::assertStringContainsString('both a recurrence and an installment plan', $response->toArray(false)['detail'] ?? '');
    }

    public function test_attach_recurrence_rejected_when_invoice_generated_from_recurrence(): void
    {
        $client = $this->createAuthenticatedClient();
        $seed = InvoiceFactory::createOne();
        $invoice = InvoiceFactory::createOne(['recurrenceSeedId' => $seed->id]);

        $response = $this->apiRequest($client, 'POST', sprintf('/api/invoices/%s/recurrence', $invoice->id->toRfc4122()), [
            'json' => $this->recurrencePayload(),
        ]);

        self::assertResponseStatusCodeSame(400);
        static::assertStringContainsString('Generated invoices cannot attach new scheduling rules.', $response->toArray(false)['detail'] ?? '');
    }

    public function test_attach_recurrence_rejected_when_invoice_generated_from_installment(): void
    {
        $client = $this->createAuthenticatedClient();
        $seed = InvoiceFactory::createOne();
        $invoice = InvoiceFactory::createOne(['installmentSeedId' => $seed->id]);

        $response = $this->apiRequest($client, 'POST', sprintf('/api/invoices/%s/recurrence', $invoice->id->toRfc4122()), [
            'json' => $this->recurrencePayload(),
        ]);

        self::assertResponseStatusCodeSame(400);
        static::assertStringContainsString('Generated invoices cannot attach new scheduling rules.', $response->toArray(false)['detail'] ?? '');
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function recurrencePayload(array $override = []): array
    {
        return array_replace([
            'frequency' => 'MONTHLY',
            'interval' => 1,
            'anchorDate' => '2025-01-01',
            'endStrategy' => 'UNTIL_DATE',
            'endDate' => '2025-12-31',
            'occurrenceCount' => null,
        ], $override);
    }
}
