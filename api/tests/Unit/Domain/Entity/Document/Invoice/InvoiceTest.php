<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Document\Invoice;

use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\DocumentLine;
use App\Domain\Entity\Document\Invoice\Invoice;
use App\Domain\Enum\InvoiceStatus;
use App\Domain\Enum\RateUnit;
use App\Domain\Exception\DocumentRuleViolationException;
use App\Domain\Exception\DocumentTransitionException;
use App\Domain\Payload\Customer\CustomerPayload;
use App\Domain\Payload\Document\DocumentLinePayload;
use App\Domain\Payload\Invoice\InvoicePayload;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Company;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Quantity;
use App\Domain\ValueObject\VatRate;
use App\Tests\Factory\Customer\CustomerFactory;
use App\Tests\Factory\Document\Invoice\InstallmentPlanFactory;
use App\Tests\Factory\ValueObject\CompanyFactory;
use App\Tests\TestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @testType solitary-unit
 */
final class InvoiceTest extends TestCase
{
    use Factories;

    private Invoice $invoice;

    protected function setUp(): void
    {
        $this->invoice = static::createInvoice();
    }

    public function test_issue_sets_status_and_dates(): void
    {
        $issuedAt = new \DateTimeImmutable('2025-06-10');
        $dueDate = new \DateTimeImmutable('2025-07-10');

        $this->invoice->issue($issuedAt, $dueDate);

        static::assertSame(InvoiceStatus::ISSUED, $this->invoice->status);
        static::assertSame($issuedAt, $this->invoice->issuedAt);
        static::assertSame($dueDate, $this->invoice->dueDate);
    }

    public function test_issue_rejected_when_not_draft(): void
    {
        $this->invoice->issue(new \DateTimeImmutable(), new \DateTimeImmutable('+1 day'));

        static::expectException(DocumentTransitionException::class);
        $this->invoice->issue(new \DateTimeImmutable(), new \DateTimeImmutable('+1 day'));
    }

    public function test_mark_overdue_only_from_issued(): void
    {
        static::expectException(DocumentTransitionException::class);
        $this->invoice->markOverdue();
    }

    public function test_mark_overdue(): void
    {
        $this->invoice->issue(new \DateTimeImmutable(), new \DateTimeImmutable('+1 day'));

        $this->invoice->markOverdue();

        static::assertSame(InvoiceStatus::OVERDUE, $this->invoice->status);
    }

    public function test_mark_paid(): void
    {
        $this->invoice->issue(new \DateTimeImmutable('2025-01-01'), new \DateTimeImmutable('2025-02-01'));

        $paidAt = new \DateTimeImmutable('2025-01-15');
        $this->invoice->markPaid($paidAt);

        static::assertSame(InvoiceStatus::PAID, $this->invoice->status);
        static::assertSame($paidAt, $this->invoice->paidAt);
    }

    public function test_void_draft(): void
    {
        $this->invoice->void();

        static::assertSame(InvoiceStatus::VOIDED, $this->invoice->status);
    }

    public function test_void_issued_with_payment_is_rejected(): void
    {
        $this->invoice->issue(new \DateTimeImmutable(), new \DateTimeImmutable('+1 day'));
        $this->invoice->markPaid(new \DateTimeImmutable('+1 day'));

        static::expectException(DocumentTransitionException::class);
        $this->invoice->void();
    }

    public function test_attach_recurrence_rejected_when_installment_plan_exists(): void
    {
        $this->invoice->attachInstallmentPlan(InstallmentPlanFactory::build()->create());

        static::expectException(DocumentRuleViolationException::class);
        $this->invoice->attachRecurrence(RecurrenceTest::createRecurrence());
    }

    public function test_detach_installment_plan(): void
    {
        $this->invoice->attachInstallmentPlan(InstallmentPlanFactory::build()->create());

        $this->invoice->detachInstallmentPlan();

        static::assertNull($this->invoice->installmentPlan);
    }

    public function test_detach_installment_plan_rejected_when_installments_already_generated(): void
    {
        $plan = InstallmentPlanTest::createInstallmentPlan([['percentage' => '100.00']]);
        $installment = $plan->installments->first();
        static::assertNotFalse($installment);
        TestHelper::setProperty($installment, 'generatedInvoiceId', Uuid::v7());

        $this->invoice->attachInstallmentPlan($plan);

        static::expectException(DocumentRuleViolationException::class);
        $this->invoice->detachInstallmentPlan();
    }

    public function test_can_attach_new_installment_plan_after_detach(): void
    {
        $firstPlan = InstallmentPlanTest::createInstallmentPlan([['percentage' => '50.00'], ['percentage' => '50.00']]);
        $replacementPlan = InstallmentPlanTest::createInstallmentPlan([['percentage' => '100.00']]);

        $this->invoice->attachInstallmentPlan($firstPlan);
        $this->invoice->detachInstallmentPlan();
        $this->invoice->attachInstallmentPlan($replacementPlan);

        static::assertSame($replacementPlan, $this->invoice->installmentPlan);
    }

    public function test_apply_payload_updates_lines_totals_and_snapshots(): void
    {
        $customer = $this->createCustomerForTest([
            'firstName' => 'Alice',
            'lastName' => 'Client',
            'legalName' => 'Client LLC',
            'email' => 'client@example.test',
            'phone' => '+33123456789',
            'streetLine1' => '10 Rue Example',
            'streetLine2' => 'Suite 10',
            'postalCode' => '75000',
            'city' => 'Paris',
            'region' => 'IDF',
            'countryCode' => 'FR',
        ]);
        $company = $this->createCompanyForTest([
            'legalName' => 'Acme SAS',
            'email' => 'hello@acme.test',
            'phone' => '+33122223333',
            'streetLine1' => '20 Rue de Lyon',
            'streetLine2' => '4th floor',
            'postalCode' => '69000',
            'city' => 'Lyon',
            'region' => 'ARA',
            'countryCode' => 'FR',
            'defaultCurrency' => 'EUR',
            'defaultHourlyRate' => '90.00',
            'defaultDailyRate' => '720.00',
            'defaultVatRate' => '20.00',
            'legalMention' => 'Payment in 30 days',
        ]);

        $invoice = Invoice::fromPayload(
            payload: new InvoicePayload(
                title: 'Initial scope',
                subtitle: 'Phase 1',
                currency: 'EUR',
                vatRate: new VatRate('20.00'),
                linesPayload: [
                    $this->createLinePayload(null, 'Initial discovery', '1.000', '100.00'),
                    $this->createLinePayload(null, 'Implementation', '2.000', '200.00'),
                ],
                dueDate: new \DateTimeImmutable('2025-01-20'),
            ),
            customer: $customer,
            company: $company,
        );

        $lines = $invoice->lines->getValues();
        TestHelper::assignUuid($lines[0], TestHelper::generateUuid(200));
        TestHelper::assignUuid($lines[1], TestHelper::generateUuid(201));
        $existingLineId = $lines[0]->id;
        $removedLineId = $lines[1]->id;

        $updatedCustomer = $this->createCustomerForTest([
            'firstName' => 'Nina',
            'lastName' => 'Builder',
            'legalName' => 'NB Industries',
            'email' => 'nina@example.test',
            'phone' => '+33999000111',
            'streetLine1' => '99 New Street',
            'streetLine2' => null,
            'postalCode' => '10001',
            'city' => 'New York',
            'region' => 'NY',
            'countryCode' => 'US',
        ]);
        $updatedCompany = $this->createCompanyForTest([
            'legalName' => 'Future Works',
            'email' => 'billing@futureworks.test',
            'phone' => '+14085551234',
            'streetLine1' => '500 Market Street',
            'streetLine2' => 'Suite 200',
            'postalCode' => '94105',
            'city' => 'San Francisco',
            'region' => 'CA',
            'countryCode' => 'US',
            'defaultCurrency' => 'USD',
            'defaultHourlyRate' => '150.00',
            'defaultDailyRate' => '900.00',
            'defaultVatRate' => '10.00',
            'legalMention' => 'Net 15.',
        ]);

        $updatePayload = new InvoicePayload(
            title: 'Updated invoice',
            subtitle: 'Phase 2',
            currency: 'USD',
            vatRate: new VatRate('10.00'),
            linesPayload: [
                $this->createLinePayload($existingLineId?->toRfc4122(), 'Revised discovery', '2.000', '300.00'),
                $this->createLinePayload(null, 'Warranty support', '1.000', '50.00'),
            ],
            dueDate: new \DateTimeImmutable('2025-03-15'),
        );

        $invoice->applyPayload($updatePayload, $updatedCustomer, $updatedCompany);

        $lines = $invoice->lines->getValues();
        static::assertCount(2, $lines);

        $updatedExisting = $this->findLineById($lines, $existingLineId);
        static::assertNotNull($updatedExisting);
        static::assertSame('Revised discovery', $updatedExisting->description);
        static::assertSame('600.00', $updatedExisting->amount->net->value);
        static::assertSame('60.00', $updatedExisting->amount->tax->value);
        static::assertSame('660.00', $updatedExisting->amount->gross->value);

        static::assertNull($this->findLineById($lines, $removedLineId));

        $newLine = null;
        foreach ($lines as $line) {
            if ('Warranty support' === $line->description) {
                $newLine = $line;
                break;
            }
        }

        static::assertNotNull($newLine);
        static::assertSame('50.00', $newLine->amount->net->value);
        static::assertSame('5.00', $newLine->amount->tax->value);
        static::assertSame('55.00', $newLine->amount->gross->value);

        static::assertSame('650.00', $invoice->total->net->value);
        static::assertSame('65.00', $invoice->total->tax->value);
        static::assertSame('715.00', $invoice->total->gross->value);
        static::assertEquals(new \DateTimeImmutable('2025-03-15'), $invoice->dueDate);
        static::assertSame('USD', $invoice->currency);
        static::assertSame('NB Industries', $invoice->customerSnapshot['legalName'] ?? null);
        static::assertSame('Future Works', $invoice->companySnapshot['legalName'] ?? null);
    }

    public function test_generated_from_recurrence_cannot_attach_recurrence(): void
    {
        $this->invoice->markGeneratedFromRecurrence(Uuid::v7());

        static::expectException(DocumentRuleViolationException::class);
        $this->invoice->attachRecurrence(RecurrenceTest::createRecurrence());
    }

    public function test_generated_from_installment_cannot_attach_recurrence(): void
    {
        $this->invoice->markGeneratedFromInstallment(Uuid::v7());

        static::expectException(DocumentRuleViolationException::class);
        $this->invoice->attachRecurrence(RecurrenceTest::createRecurrence());
    }

    public function test_generated_invoice_cannot_attach_installment_plan(): void
    {
        $this->invoice->markGeneratedFromRecurrence(Uuid::v7());

        static::expectException(DocumentRuleViolationException::class);
        $this->invoice->attachInstallmentPlan(InstallmentPlanFactory::build()->create());
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function createInvoice(): Invoice
    {
        return Invoice::fromPayload(
            payload: new InvoicePayload(
                title: 'Sample invoice',
                subtitle: null,
                currency: 'EUR',
                vatRate: new VatRate('20'),
                linesPayload: [],
                dueDate: null
            ),
            customer: CustomerFactory::build()->create(),
            company: CompanyFactory::createOne()
        );
    }

    /**
     * @param array{firstName: string, lastName: string, legalName: string, email: string, phone: string, streetLine1: string, streetLine2: ?string, postalCode: string, city: string, region: ?string, countryCode: string} $data
     */
    private function createCustomerForTest(array $data): Customer
    {
        return Customer::fromPayload(
            new CustomerPayload(
                name: new Name($data['firstName'], $data['lastName']),
                legalName: $data['legalName'],
                contact: new Contact($data['email'], $data['phone']),
                address: new Address(
                    streetLine1: $data['streetLine1'],
                    streetLine2: $data['streetLine2'],
                    postalCode: $data['postalCode'],
                    city: $data['city'],
                    region: $data['region'],
                    countryCode: $data['countryCode'],
                ),
            )
        );
    }

    /**
     * @param array{legalName: string, email: string, phone: string, streetLine1: string, streetLine2: ?string, postalCode: string, city: string, region: ?string, countryCode: string, defaultCurrency: string, defaultHourlyRate: numeric-string, defaultDailyRate: numeric-string, defaultVatRate: numeric-string, legalMention: ?string} $data
     */
    private function createCompanyForTest(array $data): Company
    {
        return new Company(
            legalName: $data['legalName'],
            contact: new Contact($data['email'], $data['phone']),
            address: new Address(
                streetLine1: $data['streetLine1'],
                streetLine2: $data['streetLine2'],
                postalCode: $data['postalCode'],
                city: $data['city'],
                region: $data['region'],
                countryCode: $data['countryCode'],
            ),
            defaultCurrency: $data['defaultCurrency'],
            defaultHourlyRate: new Money($data['defaultHourlyRate']),
            defaultDailyRate: new Money($data['defaultDailyRate']),
            defaultVatRate: new VatRate($data['defaultVatRate']),
            legalMention: $data['legalMention'],
        );
    }

    /**
     * @param numeric-string $quantity
     * @param numeric-string $rate
     */
    private function createLinePayload(
        ?string $id,
        string $description,
        string $quantity,
        string $rate,
        RateUnit $unit = RateUnit::DAILY,
    ): DocumentLinePayload {
        return new DocumentLinePayload(
            id: null === $id ? null : Uuid::fromString($id),
            description: $description,
            quantity: new Quantity($quantity),
            rateUnit: $unit,
            rate: new Money($rate),
        );
    }

    /**
     * @param list<DocumentLine> $lines
     */
    private function findLineById(array $lines, ?Uuid $id): ?DocumentLine
    {
        if (null === $id) {
            return null;
        }

        return array_find($lines, fn ($line) => null !== $line->id && $line->id->equals($id));
    }
}
