<?php

declare(strict_types=1);

namespace App\Domain\Entity\Document;

use App\Domain\Entity\Common\ArchivableTrait;
use App\Domain\Entity\Common\TimestampableTrait;
use App\Domain\Entity\Common\UuidTrait;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Guard\DomainGuard;
use App\Domain\Payload\Document\AbstractDocumentPayload;
use App\Domain\Payload\Document\DocumentLinePayload;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\VatRate;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** @phpstan-consistent-constructor */
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

        #[ORM\Embedded]
        protected(set) AmountBreakdown $total,

        #[ORM\ManyToOne(targetEntity: Customer::class)]
        #[ORM\JoinColumn(nullable: false)]
        protected(set) Customer $customer,

        /** @var array<string, mixed> */
        #[ORM\Column(type: Types::JSON)]
        protected(set) array $customerSnapshot,

        /** @var array<string, mixed> */
        #[ORM\Column(type: Types::JSON)]
        protected(set) array $companySnapshot,
    ) {
        $this->lines = new ArrayCollection();
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    protected static function fromDocumentPayload(
        AbstractDocumentPayload $payload,
        Customer $customer,
        array $customerSnapshot,
        array $companySnapshot,
    ): static {
        $document = new static(
            title: $payload->title,
            subtitle: $payload->subtitle,
            currency: $payload->currency,
            vatRate: $payload->vatRate,
            total: $payload->linesPayload->total,
            customer: $customer,
            customerSnapshot: $customerSnapshot,
            companySnapshot: $companySnapshot,
        );

        foreach ($payload->linesPayload->lines as $linePayload) {
            $document->addLine($linePayload);
        }

        return $document;
    }

    protected function applyDocumentPayload(
        AbstractDocumentPayload $payload,
        Customer $customer,
        array $customerSnapshot,
        array $companySnapshot,
    ): void {
        $this->title = $payload->title;
        $this->subtitle = $payload->subtitle;
        $this->currency = $payload->currency;
        $this->vatRate = $payload->vatRate;
        $this->total = $payload->linesPayload->total;
        $this->customer = $customer;
        $this->customerSnapshot = $customerSnapshot;
        $this->companySnapshot = $companySnapshot;

        $this->applyLinePayloads($payload->linesPayload->lines);
    }

    protected function addLine(DocumentLinePayload $payload): DocumentLine
    {
        $line = DocumentLine::fromPayload($this, $payload);

        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
        }

        return $line;
    }

    /**
     * @param list<DocumentLinePayload> $linePayloads
     */
    protected function applyLinePayloads(array $linePayloads): void
    {
        $existingLinePayloads = [];

        foreach ($linePayloads as $linePayload) {
            null !== $linePayload->lineId
                ? $existingLinePayloads[$linePayload->lineId] = $linePayload
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
}
