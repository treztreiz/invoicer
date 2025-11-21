<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Document;

use App\Domain\Contracts\Payload\DocumentPayloadInterface;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Document;
use App\Domain\Entity\Document\DocumentLine;
use App\Domain\Enum\RateUnit;
use App\Domain\Payload\Customer\CustomerPayload;
use App\Domain\Payload\Document\DocumentLinePayload;
use App\Domain\Payload\Quote\QuotePayload;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Company;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Quantity;
use App\Domain\ValueObject\VatRate;
use App\Tests\TestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * @testType solitary-unit
 */
final class DocumentTest extends TestCase
{
    public function test_from_payload_computes_line_amounts_and_totals(): void
    {
        ['entity' => $customer] = $this->createCustomerFixture();
        ['entity' => $company] = $this->createCompanyFixture();

        $document = DocumentTestStub::create(
            $this->createQuotePayload([
                ['description' => 'Discovery workshop', 'quantity' => '2.500', 'rate' => '400.00'],
                ['description' => 'Support retainer', 'quantity' => '1.000', 'rate' => '150.00', 'unit' => RateUnit::HOURLY],
            ]),
            $customer,
            $company,
        );

        $lines = $document->lines->getValues();
        static::assertCount(2, $lines);

        usort($lines, static fn (DocumentLine $left, DocumentLine $right): int => $left->position <=> $right->position);

        static::assertSame(0, $lines[0]->position);
        static::assertSame('1000.00', $lines[0]->amount->net->value);
        static::assertSame('200.00', $lines[0]->amount->tax->value);
        static::assertSame('1200.00', $lines[0]->amount->gross->value);

        static::assertSame(1, $lines[1]->position);
        static::assertSame('150.00', $lines[1]->amount->net->value);
        static::assertSame('30.00', $lines[1]->amount->tax->value);
        static::assertSame('180.00', $lines[1]->amount->gross->value);

        static::assertSame('1150.00', $document->total->net->value);
        static::assertSame('230.00', $document->total->tax->value);
        static::assertSame('1380.00', $document->total->gross->value);
    }

    public function test_snapshots_are_copied_and_immutable(): void
    {
        ['entity' => $customer, 'snapshot' => $expectedCustomerSnapshot] = $this->createCustomerFixture([
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'legalName' => 'Ada Consulting',
            'email' => 'ada@example.test',
            'phone' => '+33111111110',
            'streetLine1' => '10 Rue de Paris',
            'streetLine2' => 'Bat A',
            'postalCode' => '75001',
            'city' => 'Paris',
            'region' => 'IDF',
            'countryCode' => 'FR',
        ]);
        ['entity' => $company, 'snapshot' => $expectedCompanySnapshot] = $this->createCompanyFixture([
            'legalName' => 'Acme Studio',
            'email' => 'hello@acme.test',
            'phone' => '+33122222220',
            'streetLine1' => '20 Avenue des Arts',
            'streetLine2' => '2nd floor',
            'postalCode' => '69000',
            'city' => 'Lyon',
            'region' => 'ARA',
            'countryCode' => 'FR',
            'defaultCurrency' => 'EUR',
            'defaultHourlyRate' => '80.00',
            'defaultDailyRate' => '640.00',
            'defaultVatRate' => '20.00',
            'legalMention' => 'Payment due within 30 days.',
        ]);

        $document = DocumentTestStub::create(
            $this->createQuotePayload([
                ['description' => 'Design', 'quantity' => '1.000', 'rate' => '100.00'],
            ]),
            $customer,
            $company,
        );

        static::assertSame($expectedCustomerSnapshot, $document->customerSnapshot);
        static::assertSame($expectedCompanySnapshot, $document->companySnapshot);

        // Mutate customer
        $customer->applyPayload(
            new CustomerPayload(
                name: new Name('Grace', 'Hopper'),
                legalName: 'Grace Ops',
                contact: new Contact('grace@ops.test', '+33987654321'),
                address: new Address('99 Backup Road', null, '99999', 'Backup City', null, 'ES'),
            )
        );

        // Mutate company
        TestHelper::setProperty($company, 'legalName', 'Future Corp');
        TestHelper::setProperty($company, 'defaultCurrency', 'USD');
        TestHelper::setProperty($company, 'legalMention', 'Updated terms');

        // Ensure snapshots are not mutated
        static::assertSame($expectedCustomerSnapshot, $document->customerSnapshot);
        static::assertSame($expectedCompanySnapshot, $document->companySnapshot);
    }

    public function test_apply_payload_updates_lines_totals_and_snapshots(): void
    {
        ['entity' => $initialCustomer] = $this->createCustomerFixture();
        ['entity' => $initialCompany] = $this->createCompanyFixture();

        $document = DocumentTestStub::create(
            $this->createQuotePayload([
                ['description' => 'Initial discovery', 'quantity' => '1.000', 'rate' => '100.00'],
                ['description' => 'Implementation', 'quantity' => '2.000', 'rate' => '200.00'],
            ]),
            $initialCustomer,
            $initialCompany,
        );

        $lines = $document->lines->getValues();
        TestHelper::assignUuid($lines[0], TestHelper::generateUuid(100));
        TestHelper::assignUuid($lines[1], TestHelper::generateUuid(101));
        $existingLineId = $lines[0]->id;
        $removedLineId = $lines[1]->id;

        ['entity' => $updatedCustomer, 'snapshot' => $expectedCustomerSnapshot] = $this->createCustomerFixture([
            'firstName' => 'Nora',
            'lastName' => 'Jones',
            'legalName' => 'NJ Studio',
            'email' => 'nora@example.test',
            'phone' => '+33123000001',
            'streetLine1' => '50 Updated Street',
            'streetLine2' => null,
            'postalCode' => '54000',
            'city' => 'Nancy',
            'region' => 'Grand Est',
            'countryCode' => 'FR',
        ]);
        ['entity' => $updatedCompany, 'snapshot' => $expectedCompanySnapshot] = $this->createCompanyFixture([
            'legalName' => 'Bright Future',
            'email' => 'finance@bright.test',
            'phone' => '+33999888777',
            'streetLine1' => '77 Sunset Plaza',
            'streetLine2' => 'Suite 400',
            'postalCode' => '90210',
            'city' => 'Los Angeles',
            'region' => 'CA',
            'countryCode' => 'US',
            'defaultCurrency' => 'USD',
            'defaultHourlyRate' => '120.00',
            'defaultDailyRate' => '900.00',
            'defaultVatRate' => '10.00',
            'legalMention' => 'Net 15 days.',
        ]);

        $document->reapply(
            $this->createQuotePayload(
                [
                    [
                        'id' => $existingLineId?->toRfc4122(),
                        'description' => 'Revised discovery',
                        'quantity' => '2.000',
                        'rate' => '300.00',
                    ],
                    [
                        'description' => 'Support retainer',
                        'quantity' => '1.000',
                        'rate' => '50.00',
                    ],
                ],
                title: 'Updated scope',
                subtitle: 'Sprint 2',
                currency: 'USD',
                vatRate: '10.00',
            ),
            $updatedCustomer,
            $updatedCompany,
        );

        $lines = $document->lines->getValues();
        static::assertCount(2, $lines);

        $updatedExisting = $this->findLineById($lines, $existingLineId);
        static::assertNotNull($updatedExisting);
        static::assertSame('Revised discovery', $updatedExisting->description);
        static::assertSame(0, $updatedExisting->position);
        static::assertSame('600.00', $updatedExisting->amount->net->value);
        static::assertSame('60.00', $updatedExisting->amount->tax->value);
        static::assertSame('660.00', $updatedExisting->amount->gross->value);

        static::assertNull($this->findLineById($lines, $removedLineId));

        $newLine = null;
        foreach ($lines as $line) {
            if ('Support retainer' === $line->description) {
                $newLine = $line;
                break;
            }
        }

        static::assertNotNull($newLine);
        static::assertSame(1, $newLine->position);
        static::assertSame('50.00', $newLine->amount->net->value);
        static::assertSame('5.00', $newLine->amount->tax->value);
        static::assertSame('55.00', $newLine->amount->gross->value);

        static::assertSame('650.00', $document->total->net->value);
        static::assertSame('65.00', $document->total->tax->value);
        static::assertSame('715.00', $document->total->gross->value);
        static::assertSame('Updated scope', $document->title);
        static::assertSame('Sprint 2', $document->subtitle);
        static::assertSame('USD', $document->currency);
        static::assertSame('10.00', $document->vatRate->value);

        static::assertSame($expectedCustomerSnapshot, $document->customerSnapshot);
        static::assertSame($expectedCompanySnapshot, $document->companySnapshot);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param list<array{description: string, quantity: numeric-string, rate: numeric-string, unit?: RateUnit, id?: string|null}> $lines
     * @param numeric-string                                                                                                      $vatRate
     */
    private function createQuotePayload(
        array $lines,
        string $title = 'Consulting Sprint',
        ?string $subtitle = 'Sprint 12',
        string $currency = 'EUR',
        string $vatRate = '20.00',
    ): QuotePayload {
        return new QuotePayload(
            title: $title,
            subtitle: $subtitle,
            currency: $currency,
            vatRate: new VatRate($vatRate),
            linesPayload: array_map(
                fn (array $line) => $this->createLinePayload(
                    id: $line['id'] ?? null,
                    description: $line['description'],
                    quantity: $line['quantity'],
                    rate: $line['rate'],
                    unit: $line['unit'] ?? RateUnit::DAILY,
                ),
                $lines,
            ),
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
     * @return array{entity: Customer, snapshot: array}
     */
    private function createCustomerFixture(array $overrides = []): array
    {
        $defaults = [
            'firstName' => 'Alice',
            'lastName' => 'Customer',
            'legalName' => 'Customer LLC',
            'email' => 'client@example.test',
            'phone' => '+33123456789',
            'streetLine1' => '1 Rue Exemple',
            'streetLine2' => 'Suite 5',
            'postalCode' => '75000',
            'city' => 'Paris',
            'region' => 'IDF',
            'countryCode' => 'FR',
        ];

        $data = array_merge($defaults, $overrides);

        $customer = Customer::fromPayload(
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

        return [
            'entity' => $customer,
            'snapshot' => [
                'id' => $customer->id?->toRfc4122(),
                'legalName' => $customer->legalName,
                'name' => [
                    'first' => $customer->name->firstName,
                    'last' => $customer->name->lastName,
                ],
                'contact' => [
                    'email' => $customer->contact->email,
                    'phone' => $customer->contact->phone,
                ],
                'address' => [
                    'streetLine1' => $customer->address->streetLine1,
                    'streetLine2' => $customer->address->streetLine2,
                    'postalCode' => $customer->address->postalCode,
                    'city' => $customer->address->city,
                    'region' => $customer->address->region,
                    'countryCode' => $customer->address->countryCode,
                ],
            ],
        ];
    }

    /**
     * @return array{entity: Company, snapshot: array}
     */
    private function createCompanyFixture(array $overrides = []): array
    {
        $defaults = [
            'legalName' => 'Acme Corp',
            'email' => 'studio@example.test',
            'phone' => '+33999999999',
            'streetLine1' => '10 Avenue Victor Hugo',
            'streetLine2' => 'Floor 3',
            'postalCode' => '69001',
            'city' => 'Lyon',
            'region' => 'ARA',
            'countryCode' => 'FR',
            'defaultCurrency' => 'EUR',
            'defaultHourlyRate' => '80.00',
            'defaultDailyRate' => '640.00',
            'defaultVatRate' => '20.00',
            'legalMention' => 'Payment within 30 days.',
        ];

        $data = array_merge($defaults, $overrides);

        $company = new Company(
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

        return [
            'entity' => $company,
            'snapshot' => [
                'legalName' => $company->legalName,
                'contact' => [
                    'email' => $company->contact->email,
                    'phone' => $company->contact->phone,
                ],
                'address' => [
                    'streetLine1' => $company->address->streetLine1,
                    'streetLine2' => $company->address->streetLine2,
                    'postalCode' => $company->address->postalCode,
                    'city' => $company->address->city,
                    'region' => $company->address->region,
                    'countryCode' => $company->address->countryCode,
                ],
                'defaultCurrency' => $company->defaultCurrency,
                'defaultHourlyRate' => $company->defaultHourlyRate->value,
                'defaultDailyRate' => $company->defaultDailyRate->value,
                'defaultVatRate' => $company->defaultVatRate->value,
                'legalMention' => $company->legalMention,
            ],
        ];
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

final class DocumentTestStub extends Document
{
    public static function create(DocumentPayloadInterface $payload, Customer $customer, Company $company): self
    {
        return self::fromDocumentPayload($payload, $customer, $company);
    }

    public function reapply(DocumentPayloadInterface $payload, Customer $customer, Company $company): void
    {
        $this->applyDocumentPayload($payload, $customer, $company);
    }
}
