<?php

declare(strict_types=1);

namespace App\Domain\Entity\Document;

use App\Domain\DTO\InvoicePayload;
use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use App\Domain\Entity\Document\Invoice\InvoiceRecurrence;
use App\Domain\Enum\InvoiceStatus;
use App\Infrastructure\Doctrine\CheckAware\Attribute\EnumCheck;
use App\Infrastructure\Doctrine\CheckAware\Attribute\SoftXorCheck;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'invoice')]
#[EnumCheck(property: 'status', name: 'CHK_INVOICE_STATUS')]
#[SoftXorCheck(properties: ['recurrence', 'installmentPlan'], name: 'CHK_INVOICE_SCHEDULE_XOR')]
class Invoice extends Document
{
    #[ORM\Column(enumType: InvoiceStatus::class)]
    private(set) InvoiceStatus $status = InvoiceStatus::DRAFT;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private(set) ?\DateTimeImmutable $issuedAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private(set) ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private(set) ?\DateTimeImmutable $paidAt = null;

    #[ORM\OneToOne(targetEntity: InvoiceRecurrence::class, inversedBy: 'invoice', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private(set) ?InvoiceRecurrence $recurrence = null;

    #[ORM\OneToOne(targetEntity: InstallmentPlan::class, inversedBy: 'invoice', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private(set) ?InstallmentPlan $installmentPlan = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private(set) ?Uuid $recurrenceSeedId = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private(set) ?Uuid $installmentSeedId = null;

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function fromPayload(InvoicePayload $payload): self
    {
        $invoice = new self(
            title: $payload->title,
            currency: $payload->currency,
            vatRate: $payload->vatRate,
            total: $payload->total,
            customerSnapshot: $payload->customerSnapshot,
            companySnapshot: $payload->companySnapshot,
            subtitle: $payload->subtitle,
        );

        foreach ($payload->lines as $linePayload) {
            $invoice->addLine($linePayload);
        }

        $invoice->dueDate = $payload->dueDate;

        return $invoice;
    }

    public function issue(\DateTimeImmutable $issuedAt, \DateTimeImmutable $dueDate): self
    {
        if (InvoiceStatus::DRAFT !== $this->status) {
            throw new \LogicException('Only draft invoices can be issued.');
        }

        if ($dueDate < $issuedAt) {
            throw new \LogicException('Due date must be on or after the issue date.');
        }

        $this->status = InvoiceStatus::ISSUED;
        $this->issuedAt = $issuedAt;
        $this->dueDate = $dueDate;

        return $this;
    }

    public function markOverdue(): self
    {
        if (InvoiceStatus::ISSUED !== $this->status) {
            throw new \LogicException('Only issued invoices can become overdue.');
        }

        $this->status = InvoiceStatus::OVERDUE;

        return $this;
    }

    public function markPaid(\DateTimeImmutable $paidAt): self
    {
        if (!in_array($this->status, [InvoiceStatus::ISSUED, InvoiceStatus::OVERDUE], true)) {
            throw new \LogicException('Only issued or overdue invoices can be marked as paid.');
        }

        if (null !== $this->issuedAt && $paidAt < $this->issuedAt) {
            throw new \LogicException('Payment date cannot precede the issue date.');
        }

        $this->status = InvoiceStatus::PAID;
        $this->paidAt = $paidAt;

        return $this;
    }

    public function void(): self
    {
        if (!in_array($this->status, [InvoiceStatus::DRAFT, InvoiceStatus::ISSUED], true)) {
            throw new \LogicException('Only draft or issued invoices can be voided.');
        }

        if (InvoiceStatus::ISSUED === $this->status && null !== $this->paidAt) {
            throw new \LogicException('Cannot void an invoice that has registered payments.');
        }

        $this->status = InvoiceStatus::VOIDED;
        $this->issuedAt = null;
        $this->dueDate = null;

        return $this;
    }

    public function revertToDraft(): self
    {
        if (InvoiceStatus::VOIDED !== $this->status) {
            throw new \LogicException('Only voided invoices can revert to draft.');
        }

        $this->status = InvoiceStatus::DRAFT;

        return $this;
    }

    public function attachRecurrence(InvoiceRecurrence $recurrence): void
    {
        $this->assertNotGeneratedFromSeed();

        if (null !== $this->installmentPlan) {
            throw new \LogicException('Invoices cannot have both a recurrence and an installment plan.');
        }

        $this->recurrence = $recurrence;
    }

    public function attachInstallmentPlan(InstallmentPlan $plan): void
    {
        $this->assertNotGeneratedFromSeed();

        if (null !== $this->recurrence) {
            throw new \LogicException('Invoices cannot have both an installment plan and a recurrence.');
        }

        $this->installmentPlan = $plan;
    }

    public function detachRecurrence(): void
    {
        $this->recurrence = null;
    }

    public function markGeneratedFromRecurrence(Uuid $seedId): void
    {
        $this->recurrenceSeedId = $seedId;
    }

    public function markGeneratedFromInstallment(Uuid $seedId): void
    {
        $this->installmentSeedId = $seedId;
    }

    private function assertNotGeneratedFromSeed(): void
    {
        if (null !== $this->recurrenceSeedId || null !== $this->installmentSeedId) {
            throw new \LogicException('Generated invoices cannot attach new scheduling rules.');
        }
    }
}
