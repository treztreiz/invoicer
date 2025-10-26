<?php

namespace App\Domain\Entity\Document;

use App\Domain\DTO\DocumentLinePayload;
use App\Domain\Entity\Common\ArchivableTrait;
use App\Domain\Entity\Common\TimestampableTrait;
use App\Domain\Entity\Common\UuidTrait;
use App\Domain\Guard\DomainGuard;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VatRate;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

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

    /** @var Collection<int, DocumentLine> */
    #[ORM\OneToMany(targetEntity: DocumentLine::class, mappedBy: 'document', cascade: ['persist'], orphanRemoval: true)]
    protected(set) Collection $lines;

    /**
     * @param array<string, mixed> $customerSnapshot
     * @param array<string, mixed> $companySnapshot
     */
    public function __construct(
        #[ORM\Column(length: 200)]
        protected(set) string $title,

        #[ORM\Column(length: 3)]
        protected(set) string $currency {
            set => DomainGuard::currency($value);
        },

        #[ORM\Embedded]
        protected(set) VatRate $vatRate,

        #[ORM\Column(type: Types::JSON)]
        protected(set) array $customerSnapshot,

        #[ORM\Column(type: Types::JSON)]
        protected(set) array $companySnapshot,

        #[ORM\Embedded]
        protected(set) Money $subtotalNet,

        #[ORM\Embedded]
        protected(set) Money $taxTotal,

        #[ORM\Embedded]
        protected(set) Money $grandTotal,

        #[ORM\Column(length: 200, nullable: true)]
        protected(set) ?string $subtitle = null,

        #[ORM\Column(length: 30, nullable: true)]
        protected(set) ?string $reference = null,
    ) {
        $this->lines = new ArrayCollection();
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    protected function assignReference(string $reference): void
    {
        $this->reference = DomainGuard::nonEmpty($reference, 'Reference');
    }

    protected function addLine(DocumentLinePayload $payload): DocumentLine
    {
        $line = DocumentLine::fromPayload($this, $payload);

        $this->registerLine($line);

        return $line;
    }

    protected function registerLine(DocumentLine $line): void
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
        }
    }

    protected function removeLine(DocumentLine $line): void
    {
        $this->lines->removeElement($line);
    }

    protected function updateTotals(Money $subtotalNet, Money $taxTotal, Money $grandTotal): void
    {
        $this->subtotalNet = $subtotalNet;
        $this->taxTotal = $taxTotal;
        $this->grandTotal = $grandTotal;
    }
}
