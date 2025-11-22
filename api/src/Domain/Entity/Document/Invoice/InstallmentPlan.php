<?php

declare(strict_types=1);

namespace App\Domain\Entity\Document\Invoice;

use App\Domain\Entity\Common\TimestampableTrait;
use App\Domain\Entity\Common\UuidTrait;
use App\Domain\Exception\DocumentRuleViolationException;
use App\Domain\Payload\Invoice\Installment\ComputedInstallmentPayload;
use App\Domain\Payload\Invoice\Installment\InstallmentPayload;
use App\Domain\Payload\Invoice\Installment\InstallmentPlanPayload;
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
    private(set) Collection $installments {
        get => $this->installments ?? $this->installments = new ArrayCollection();
        set => $value;
    }

    #[ORM\OneToOne(targetEntity: Invoice::class, mappedBy: 'installmentPlan')]
    private(set) ?Invoice $invoice = null;

    private function __construct()
    {
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

    // INSTALLMENTS ////////////////////////////////////////////////////////////////////////////////////////////////////

    /** @param list<InstallmentPayload> $installmentPayloads */
    private function synchronizeInstallments(array $installmentPayloads, AmountBreakdown $invoiceTotal): void
    {
        $amounts = $this->allocateAmounts($installmentPayloads, $invoiceTotal);

        $existingInstallments = [];
        foreach ($this->installments as $installment) {
            if ($installment->id) {
                $existingInstallments[$installment->id->toRfc4122()] = $installment;
            } else {
                $this->removeInstallment($installment);
            }
        }

        foreach ($installmentPayloads as $position => $payload) {
            $computedInstallment = new ComputedInstallmentPayload(
                payload: $payload,
                position: $position,
                amount: $amounts[$position],
            );

            $id = $payload->id?->toRfc4122();

            if (null !== $id && isset($existingInstallments[$id])) {
                if (false === $existingInstallments[$id]->equals($computedInstallment)) {
                    $existingInstallments[$id]->applyPayload($computedInstallment);
                }
                unset($existingInstallments[$id]);
            } else {
                $this->addInstallment($computedInstallment);
            }
        }

        foreach ($existingInstallments as $installment) {
            $this->removeInstallment($installment);
        }
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
        $this->assertTotalEqualsOneHundred($percentages);

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

    private function addInstallment(ComputedInstallmentPayload $installmentPayload): Installment
    {
        $installment = Installment::fromPayload($installmentPayload, $this);

        if (!$this->installments->contains($installment)) {
            $this->installments->add($installment);
        }

        return $installment;
    }

    private function removeInstallment(Installment $installment): void
    {
        $installment->assertMutable();
        $this->installments->removeElement($installment);
    }

    public function getNextPendingInstallment(): ?Installment
    {
        return $this->installments->findFirst(
            fn (int $i, Installment $installment) => !$installment->isGenerated()
        ) ?: null;
    }

    // GUARDS //////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param array<numeric-string> $percentages
     */
    private function assertTotalEqualsOneHundred(array $percentages): void
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

    public function assertNextInstallment(Installment $installment): void
    {
        $next = $this->getNextPendingInstallment();

        if (null === $next || $next !== $installment) {
            throw new DocumentRuleViolationException('Installments must be generated sequentially.');
        }
    }
}
