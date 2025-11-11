<?php

declare(strict_types=1);

namespace App\Domain\Entity\Document\Invoice;

use App\Domain\DTO\InstallmentPayload;
use App\Domain\DTO\InstallmentPlanPayload;
use App\Domain\Entity\Common\TimestampableTrait;
use App\Domain\Entity\Common\UuidTrait;
use App\Domain\Entity\Document\Invoice;
use App\Domain\ValueObject\AmountBreakdown;
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

    #[ORM\OneToOne(targetEntity: Invoice::class, mappedBy: 'installmentPlan')]
    private(set) ?Invoice $invoice = null;

    public function __construct()
    {
        $this->installments = new ArrayCollection();
    }

    public static function fromPayload(InstallmentPlanPayload $payload): self
    {
        $plan = new self();

        foreach ($payload->installments as $installmentPayload) {
            $plan->addInstallmentPayload($installmentPayload);
        }

        return $plan;
    }

    public function addInstallment(int $position, string $percentage, AmountBreakdown $amount, ?\DateTimeImmutable $dueDate = null): Installment
    {
        $installment = new Installment(
            installmentPlan: $this,
            position: $position,
            percentage: $percentage,
            amount: $amount,
            dueDate: $dueDate,
        );

        $this->registerInstallment($installment);

        return $installment;
    }

    private function addInstallmentPayload(InstallmentPayload $payload): void
    {
        $this->addInstallment(
            position: $payload->position,
            percentage: $payload->percentage,
            amount: $payload->amount,
            dueDate: $payload->dueDate,
        );
    }

    /**
     * @return list<Installment>
     */
    public function installments(): array
    {
        return array_values($this->installments->toArray());
    }

    private function registerInstallment(Installment $installment): void
    {
        if (!$this->installments->contains($installment)) {
            $this->installments->add($installment);
        }
    }
}
