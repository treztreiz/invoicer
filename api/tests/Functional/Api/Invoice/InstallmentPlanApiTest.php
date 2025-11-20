<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Tests\Functional\Api\Invoice;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Factory\Document\Invoice\InstallmentFactory;
use App\Tests\Factory\Document\Invoice\InstallmentPlanFactory;
use App\Tests\Factory\Document\Invoice\InvoiceFactory;
use App\Tests\Functional\Api\Common\ApiClientHelperTrait;

/**
 * @testType functional
 */
final class InstallmentPlanApiTest extends ApiTestCase
{
    use ApiClientHelperTrait;

    protected static ?bool $alwaysBootKernel = true;

    public function test_attach_installment_plan(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::createOne();

        static::assertNull($invoice->installmentPlan);

        $response = $this->apiRequest($client, 'POST', sprintf('/api/invoices/%s/installment-plan', $invoice->id->toRfc4122()), [
            'json' => $this->installmentPayload(),
        ]);

        self::assertResponseIsSuccessful();
        static::assertCount(2, $response->toArray(false)['installmentPlan']['installments']);

        static::assertNotNull($invoice->installmentPlan);
        static::assertCount(2, $invoice->installmentPlan->installments);
    }

    public function test_update_installment_plan(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::new()->withInstallmentPlan(3)->create();

        static::assertCount(3, $invoice->installmentPlan->installments);

        $response = $this->apiRequest($client, 'PUT', sprintf('/api/invoices/%s/installment-plan', $invoice->id->toRfc4122()), [
            'json' => $this->installmentPayload([
                ['percentage' => 40, 'dueDate' => '2025-01-15'],
                ['percentage' => 60, 'dueDate' => '2025-02-15'],
            ]),
        ]);

        self::assertResponseIsSuccessful();

        $data = $response->toArray(false);
        static::assertCount(2, $data['installmentPlan']['installments'] ?? 0);
        static::assertSame('40.00', $data['installmentPlan']['installments'][0]['percentage']);
        static::assertSame('60.00', $data['installmentPlan']['installments'][1]['percentage']);

        // Ensure invoice's installments are correctly updated
        $installments = $invoice->installmentPlan->installments;
        static::assertCount(2, $installments);
        static::assertSame('40.00', $installments->first()->percentage);
        static::assertSame('60.00', $installments->last()->percentage);

        // Ensure old installments are deleted from database
        InstallmentFactory::assert()->count(2);
    }

    public function test_delete_installment_plan(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::new()->withInstallmentPlan(3)->create();

        static::assertNotNull($invoice->installmentPlan);
        InstallmentPlanFactory::assert()->count(1);
        InstallmentFactory::assert()->count(3);

        $response = $this->apiRequest($client, 'DELETE', sprintf('/api/invoices/%s/installment-plan', $invoice->id->toRfc4122()));

        self::assertResponseIsSuccessful();
        static::assertNull($response->toArray(false)['installmentPlan']);

        static::assertNull($invoice->installmentPlan);
        InstallmentPlanFactory::assert()->empty();
        InstallmentFactory::assert()->empty();
    }

    public function test_attach_installment_plan_rejected_when_recurrence_exists(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::new()->withRecurrence()->create();

        $response = $this->apiRequest($client, 'POST', sprintf('/api/invoices/%s/installment-plan', $invoice->id->toRfc4122()), [
            'json' => $this->installmentPayload(),
        ]);

        self::assertResponseStatusCodeSame(400);
        static::assertStringContainsString('both an installment plan and a recurrence', $response->toArray(false)['detail'] ?? '');
    }

    public function test_attach_installment_plan_rejected_when_invoice_generated_from_recurrence(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::new()->generatedFromRecurrence()->create();

        $response = $this->apiRequest($client, 'POST', sprintf('/api/invoices/%s/installment-plan', $invoice->id->toRfc4122()), [
            'json' => $this->installmentPayload(),
        ]);

        self::assertResponseStatusCodeSame(400);
        static::assertStringContainsString('Generated invoices cannot attach new scheduling rules.', $response->toArray(false)['detail'] ?? '');
    }

    public function test_attach_installment_plan_rejected_when_invoice_generated_from_installment(): void
    {
        $client = $this->createAuthenticatedClient();
        $invoice = InvoiceFactory::new()->generatedFromInstallment()->create();

        $response = $this->apiRequest($client, 'POST', sprintf('/api/invoices/%s/installment-plan', $invoice->id->toRfc4122()), [
            'json' => $this->installmentPayload(),
        ]);

        self::assertResponseStatusCodeSame(400);
        static::assertStringContainsString('Generated invoices cannot attach new scheduling rules.', $response->toArray(false)['detail'] ?? '');
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function installmentPayload(array $installments = []): array
    {
        if ([] === $installments) {
            $installments = [
                ['percentage' => 50, 'dueDate' => '2025-01-01'],
                ['percentage' => 50, 'dueDate' => '2025-02-01'],
            ];
        }

        return ['installments' => array_values($installments)];
    }
}
