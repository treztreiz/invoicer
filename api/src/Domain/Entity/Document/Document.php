<?php

namespace App\Domain\Entity\Document;

use App\Domain\Entity\Common\ArchivableTrait;
use App\Domain\Entity\Common\TimestampableTrait;
use App\Domain\Entity\Common\UuidTrait;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;
use App\Domain\ValueObject\VatRate;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

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

        #[ORM\Column(length: 200, nullable: true)]
        protected(set) ?string $subtitle = null,

        #[ORM\Column(length: 30, nullable: true)]
        protected(set) ?string $reference = null,

        #[ORM\Column(length: 3)]
        protected(set) string $currency {
            get => $this->currency;
            set {
                $this->assertCurrency($value);
                $this->currency = strtoupper($value);
            }
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
    ) {
        $this->lines = new ArrayCollection();
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    protected function addLine(
        string $description,
        Quantity $quantity,
        Money $unitPrice,
        Money $amountNet,
        Money $amountTax,
        Money $amountGross,
        int $position
    ): DocumentLine {
        $line = new DocumentLine(
            $this,
            $description,
            $quantity,
            $unitPrice,
            $amountNet,
            $amountTax,
            $amountGross,
            $position
        );

        $this->registerLine($line);

        return $line;
    }

    protected function assignReference(string $reference): void
    {
        if (trim($reference) === '') {
            throw new InvalidArgumentException('Reference cannot be blank.');
        }

        $this->reference = $reference;
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

    private function assertCurrency(string $currency): void
    {
        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException('Currency must be a 3-letter ISO 4217 code.');
        }
    }
}
