<?php

declare(strict_types=1);

namespace App\Domain\Entity\Document;

use App\Domain\Contracts\Payload\DocumentPayloadInterface;
use App\Domain\Entity\Common\ArchivableTrait;
use App\Domain\Entity\Common\TimestampableTrait;
use App\Domain\Entity\Common\UuidTrait;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Invoice\Invoice;
use App\Domain\Entity\Document\Quote\Quote;
use App\Domain\Exception\DocumentRuleViolationException;
use App\Domain\Guard\DomainGuard;
use App\Domain\Payload\Document\ComputedLinePayload;
use App\Domain\Service\MoneyMath;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Company;
use App\Domain\ValueObject\VatRate;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @phpstan-consistent-constructor
 */
#[ORM\Entity]
#[ORM\Table(name: 'document')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string', length: 10)]
#[ORM\DiscriminatorMap(['QUOTE' => Quote::class, 'INVOICE' => Invoice::class])]
abstract class Document
{
    use UuidTrait;
    use TimestampableTrait;
    use ArchivableTrait;

    #[ORM\Column(length: 30, nullable: true)]
    protected(set) ?string $reference {
        get => $this->reference ?? null;
        set => DomainGuard::optionalNonEmpty($value, 'Reference');
    }

    /** @var ArrayCollection<int, DocumentLine> */
    #[ORM\OneToMany(targetEntity: DocumentLine::class, mappedBy: 'document', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected(set) Collection $lines {
        get => $this->lines ?? $this->lines = new ArrayCollection();
        set => $value;
    }

    #[ORM\Embedded]
    protected(set) AmountBreakdown $total;

    /** @var array<string, mixed> $customerSnapshot */
    #[ORM\Column(type: Types::JSON)]
    protected(set) array $customerSnapshot = [];

    /** @var array<string, mixed> $companySnapshot */
    #[ORM\Column(type: Types::JSON)]
    protected(set) array $companySnapshot = [];

    protected function __construct(
        #[ORM\Column(length: 200)]
        protected(set) string $title {
            set => DomainGuard::nonEmpty($value, 'Title');
        },

        #[ORM\Column(length: 200, nullable: true)]
        protected(set) ?string $subtitle {
            get => $this->subtitle ?? null;
            set => DomainGuard::optionalNonEmpty($value, 'Subtitle');
        },

        #[ORM\Column(length: 3)]
        protected(set) string $currency {
            set => DomainGuard::currency($value);
        },

        #[ORM\Embedded]
        protected(set) VatRate $vatRate,

        #[ORM\ManyToOne(targetEntity: Customer::class)]
        #[ORM\JoinColumn(nullable: false)]
        protected(set) Customer $customer,
    ) {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param Customer|array<string, mixed> $customer
     * @param Company|array<string, mixed>  $company
     */
    protected static function fromDocumentPayload(
        DocumentPayloadInterface $payload,
        Customer|array $customer,
        Company|array $company,
    ): static {
        $document = new static(
            title: $payload->title,
            subtitle: $payload->subtitle,
            currency: $payload->currency,
            vatRate: $payload->vatRate,
            customer: $customer instanceof Customer ? $customer : $payload->customer,
        );

        $document->computePayload($payload);
        $document->updateSnapshots($customer, $company);

        return $document;
    }

    /**
     * @param Customer|array<string, mixed> $customer
     * @param Company|array<string, mixed>  $company
     */
    protected function applyDocumentPayload(
        DocumentPayloadInterface $payload,
        Customer|array $customer,
        Company|array $company,
    ): void {
        $this->title = $payload->title;
        $this->subtitle = $payload->subtitle;
        $this->currency = $payload->currency;
        $this->vatRate = $payload->vatRate;
        $this->customer = $customer instanceof Customer ? $customer : $payload->customer;

        $this->computePayload($payload);
        $this->updateSnapshots($customer, $company);
    }

    private function computePayload(DocumentPayloadInterface $payload): void
    {
        $computedLines = [];
        $totalNet = '0.00';
        $totalTax = '0.00';

        // compute lines and total
        foreach ($payload->linesPayload as $position => $linePayload) {
            $net = MoneyMath::multiply($linePayload->quantity->value, $linePayload->rate->value);
            $tax = MoneyMath::percentage($net, $payload->vatRate->value);
            $gross = MoneyMath::add($net, $tax);

            $computedLines[] = new ComputedLinePayload(
                payload: $linePayload,
                amount: AmountBreakdown::fromValues($net, $tax, $gross),
                position: $position,
            );

            $totalNet = MoneyMath::add($totalNet, $net);
            $totalTax = MoneyMath::add($totalTax, $tax);
        }

        // Update total
        $this->total = AmountBreakdown::fromValues(
            net: $totalNet,
            tax: $totalTax,
            gross: MoneyMath::add($totalNet, $totalTax)
        );

        // Apply lines
        $this->applyLinePayloads($computedLines);
    }

    // DOCUMENT LINES //////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param list<ComputedLinePayload> $linePayloads
     */
    private function applyLinePayloads(array $linePayloads): void
    {
        $existingLinePayloads = [];

        foreach ($linePayloads as $linePayload) {
            null !== $linePayload->id
                ? $existingLinePayloads[$linePayload->id->toRfc4122()] = $linePayload
                : $this->addLine($linePayload);
        }

        $existingLines = $this->lines->filter(fn (DocumentLine $line) => null !== $line->id);

        foreach ($existingLines as $line) {
            $lineId = $line->id->toRfc4122();

            isset($existingLinePayloads[$lineId])
                ? $line->applyPayload($existingLinePayloads[$lineId])
                : $this->lines->removeElement($line);
        }
    }

    private function addLine(ComputedLinePayload $payload): DocumentLine
    {
        $line = DocumentLine::fromPayload($this, $payload);

        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
        }

        return $line;
    }

    // SNAPSHOTS ///////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param Customer|array<string, mixed> $customerSnapshot
     * @param Company|array<string, mixed>  $companySnapshot
     */
    private function updateSnapshots(Customer|array $customerSnapshot, Company|array $companySnapshot): void
    {
        if ($customerSnapshot instanceof Customer) {
            $customerSnapshot = $this->computeCustomerSnapshot($customerSnapshot);
        }

        $this->customerSnapshot = $this->assertCustomerSnapshot($customerSnapshot);

        if ($companySnapshot instanceof Company) {
            $companySnapshot = $this->computeCompanySnapshot($companySnapshot);
        }

        $this->companySnapshot = $this->assertCompanySnapshot($companySnapshot);
    }

    /**
     * @return array<string, mixed>
     */
    private function computeCustomerSnapshot(Customer $customer): array
    {
        return [
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
        ];
    }

    /**
     * @param array<string, mixed> $snapshot
     *
     * @return array<string, mixed>
     */
    private function assertCustomerSnapshot(array $snapshot): array
    {
        $snapshotKeys = ['id', 'legalName', 'contact', 'address'];
        $nameKeys = ['first', 'last'];
        $contactKeys = ['email', 'phone'];
        $addressKeys = ['streetLine1', 'streetLine2', 'city', 'postalCode', 'region', 'countryCode'];

        if (
            [] !== array_diff($snapshotKeys, array_keys($snapshot))
            || [] !== array_diff($nameKeys, array_keys($snapshot['name']))
            || [] !== array_diff($contactKeys, array_keys($snapshot['contact']))
            || [] !== array_diff($addressKeys, array_keys($snapshot['address']))
        ) {
            throw new DocumentRuleViolationException('Customer snapshot is invalid.');
        }

        return $snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    private function computeCompanySnapshot(Company $company): array
    {
        return [
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
        ];
    }

    /**
     * @param array<string, mixed> $snapshot
     *
     * @return array<string, mixed>
     */
    private function assertCompanySnapshot(array $snapshot): array
    {
        $snapshotKeys = ['legalName', 'contact', 'address', 'defaultCurrency', 'defaultHourlyRate', 'defaultDailyRate', 'defaultVatRate', 'legalMention'];
        $contactKeys = ['email', 'phone'];
        $addressKeys = ['streetLine1', 'streetLine2', 'city', 'postalCode', 'region', 'countryCode'];

        if (
            [] !== array_diff($snapshotKeys, array_keys($snapshot))
            || [] !== array_diff($contactKeys, array_keys($snapshot['contact']))
            || [] !== array_diff($addressKeys, array_keys($snapshot['address']))
        ) {
            throw new DocumentRuleViolationException('Company snapshot is invalid.');
        }

        return $snapshot;
    }
}
