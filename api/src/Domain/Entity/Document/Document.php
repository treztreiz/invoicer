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
 *
 * @phpstan-type NameSnapshot array{first: string, last: string}
 * @phpstan-type ContactSnapshot array{email: string|null, phone: string|null}
 * @phpstan-type AddressSnapshot array{streetLine1: string, streetLine2: string|null, postalCode: string, city: string, region: string|null, countryCode: string}
 * @phpstan-type CustomerSnapshot array{}|array{id: string|null, legalName: string|null, name: NameSnapshot, contact: ContactSnapshot, address: AddressSnapshot}
 * @phpstan-type CompanySnapshot array{}|array{legalName: string, contact: ContactSnapshot, address: AddressSnapshot, defaultCurrency: string, defaultHourlyRate: string, defaultDailyRate: string, defaultVatRate: string, legalMention: string|null}
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
    protected(set) Collection $lines;

    #[ORM\Embedded]
    protected(set) AmountBreakdown $total;

    /** @var CustomerSnapshot $customerSnapshot */
    #[ORM\Column(type: Types::JSON)]
    protected(set) array $customerSnapshot = [];

    /** @var CompanySnapshot $companySnapshot */
    #[ORM\Column(type: Types::JSON)]
    protected(set) array $companySnapshot = [];

    public function __construct(
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
        $this->lines = new ArrayCollection();
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    protected static function fromDocumentPayload(
        DocumentPayloadInterface $payload,
        Customer $customer,
        Company $company,
    ): static {
        $document = new static(
            title: $payload->title,
            subtitle: $payload->subtitle,
            currency: $payload->currency,
            vatRate: $payload->vatRate,
            customer: $customer,
        );

        $document->computePayload($payload);
        $document->computeCustomerSnapshot($customer);
        $document->computeCompanySnapshot($company);

        return $document;
    }

    protected function applyDocumentPayload(
        DocumentPayloadInterface $payload,
        Customer $customer,
        Company $company,
    ): void {
        $this->title = $payload->title;
        $this->subtitle = $payload->subtitle;
        $this->currency = $payload->currency;
        $this->vatRate = $payload->vatRate;
        $this->customer = $customer;

        $this->computePayload($payload);
        $this->computeCustomerSnapshot($customer);
        $this->computeCompanySnapshot($company);
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

    private function computeCustomerSnapshot(Customer $customer): void
    {
        $this->customerSnapshot = [
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

    private function computeCompanySnapshot(Company $company): void
    {
        $this->companySnapshot = [
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
}
