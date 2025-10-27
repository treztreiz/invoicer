<?php

declare(strict_types=1);

namespace App\Domain\Entity\Document\Invoice;

use App\Domain\Entity\Common\TimestampableTrait;
use App\Domain\Entity\Common\UuidTrait;
use App\Domain\Entity\Document\Invoice;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'installment_plan')]
class InstallmentPlan
{
    use UuidTrait;
    use TimestampableTrait;

    /** @var ArrayCollection<int, Installment> */
    #[ORM\OneToMany(targetEntity: Installment::class, mappedBy: 'installmentPlan', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private(set) Collection $installments;

    public function __construct(
        #[ORM\OneToOne(targetEntity: Invoice::class, inversedBy: 'installmentPlan')]
        #[ORM\JoinColumn(unique: true, nullable: false, onDelete: 'CASCADE')]
        private(set) readonly Invoice $invoice,
    ) {
        $this->installments = new ArrayCollection();
    }
}
