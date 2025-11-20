<?php

declare(strict_types=1);

namespace App\Domain\Entity\Document\Invoice;

use App\Domain\Entity\Common\TimestampableTrait;
use App\Domain\Entity\Common\UuidTrait;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Exception\DocumentRuleViolationException;
use App\Domain\Payload\Document\Invoice\AllocatedInstallmentPayload;
use App\Domain\Payload\Document\Invoice\InstallmentPayload;
use App\Domain\Payload\Document\Invoice\InstallmentPlanPayload;
use App\Domain\Service\MoneyMath;
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

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function fromPayload(InstallmentPlanPayload $payload, AmountBreakdown $invoiceTotal): self
    {
        $plan = new self();
        $plan->synchronizeInstallments($payload->installments, $invoiceTotal);

        return $plan;
    }

    public function applyPayload(InstallmentPlanPayload $payload): void
    {
        if (null === $this->invoice) {
            throw new \LogicException('Installment plan must be attached to an invoice before being updated.');
        }

        $this->synchronizeInstallments($payload->installments, $this->invoice->total);
    }

    private function addInstallment(AllocatedInstallmentPayload $installmentPayload): Installment
    {
        $installment = Installment::fromPayload($installmentPayload, $this);

        if (!$this->installments->contains($installment)) {
            $this->installments->add($installment);
        }

        return $installment;
    }

    /** @param list<InstallmentPayload> $installmentPayloads */
    private function synchronizeInstallments(array $installmentPayloads, AmountBreakdown $invoiceTotal): void
    {
        $amounts = $this->allocateAmounts($installmentPayloads, $invoiceTotal);
        $existingInstallments = $this->getInstallmentsById();

        foreach ($installmentPayloads as $position => $payload) {
            $allocatedPayload = new AllocatedInstallmentPayload(
                position: $position,
                percentage: $payload->percentage,
                amount: $amounts[$position],
                dueDate: $payload->dueDate
            );

            $id = $payload->id?->toRfc4122();

            if (null !== $id && isset($existingInstallments[$id])) {
                $existingInstallments[$id]->applyPayload($allocatedPayload);
                unset($existingInstallments[$id]);

                continue;
            }

            $this->addInstallment($allocatedPayload);
        }

        foreach ($existingInstallments as $installment) {
            $installment->assertMutable();
            $this->installments->removeElement($installment);
        }
    }

    /**
     * @return array<string, Installment>
     */
    private function getInstallmentsById(): array
    {
        $indexed = [];

        foreach ($this->installments as $installment) {
            if ($installment->id) {
                $indexed[$installment->id->toRfc4122()] = $installment;
            }
        }

        return $indexed;
    }

    /**
     * @param array<InstallmentPayload> $installmentPayloads
     *
     * @return array<int, AmountBreakdown>
     */
    private function allocateAmounts(array $installmentPayloads, AmountBreakdown $total): array
    {
        /** @var array<numeric-string> $percentages */
        $percentages = array_map(static fn (InstallmentPayload $installment) => $installment->percentage, $installmentPayloads);
        $this->assertTotal($percentages);

        $amounts = [];
        $accumulatedNet = '0.00';
        $accumulatedTax = '0.00';
        $accumulatedGross = '0.00';

        foreach ($percentages as $position => $percentage) {
            $net = MoneyMath::percentage($total->net->value, $percentage);
            $tax = MoneyMath::percentage($total->tax->value, $percentage);
            $gross = MoneyMath::percentage($total->gross->value, $percentage);

            $accumulatedNet = MoneyMath::add($accumulatedNet, $net);
            $accumulatedTax = MoneyMath::add($accumulatedTax, $tax);
            $accumulatedGross = MoneyMath::add($accumulatedGross, $gross);

            $amounts[$position] = AmountBreakdown::fromValues($net, $tax, $gross);
        }

        $lastPosition = array_key_last($amounts);
        if (null !== $lastPosition) {
            $amounts[$lastPosition] = AmountBreakdown::fromValues(
                net: MoneyMath::add(
                    $amounts[$lastPosition]->net->value,
                    MoneyMath::subtract($total->net->value, $accumulatedNet)
                ),
                tax: MoneyMath::add(
                    $amounts[$lastPosition]->tax->value,
                    MoneyMath::subtract($total->tax->value, $accumulatedTax)
                ),
                gross: MoneyMath::add(
                    $amounts[$lastPosition]->gross->value,
                    MoneyMath::subtract($total->gross->value, $accumulatedGross)
                )
            );
        }

        return $amounts;
    }

    /**
     * @param array<numeric-string> $percentages
     */
    private function assertTotal(array $percentages): void
    {
        $total = array_reduce($percentages, fn (string $carry, string $value) => MoneyMath::add($carry, $value), '0.00');

        if (0 !== \bccomp($total, '100.00', 2)) {
            throw new DocumentRuleViolationException('Installment percentages must total 100.');
        }
    }

    public function assertDetachable(): void
    {
        foreach ($this->installments as $installment) {
            $installment->assertMutable();
        }
    }
}
