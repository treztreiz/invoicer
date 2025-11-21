<?php

declare(strict_types=1);

namespace App\Domain\Entity\Document\Invoice;

use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Document;
use App\Domain\Enum\InvoiceStatus;
use App\Domain\Exception\DocumentRuleViolationException;
use App\Domain\Exception\DocumentTransitionException;
use App\Domain\Payload\Invoice\Installment\InstallmentPlanPayload;
use App\Domain\Payload\Invoice\InvoicePayload;
use App\Domain\Payload\Invoice\Recurrence\RecurrencePayload;
use App\Domain\ValueObject\Company;
use App\Infrastructure\Doctrine\CheckAware\Attribute\EnumCheck;
use App\Infrastructure\Doctrine\CheckAware\Attribute\SoftXorCheck;
use App\Infrastructure\Doctrine\Repository\InvoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
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

    #[ORM\OneToOne(targetEntity: Recurrence::class, inversedBy: 'invoice', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private(set) ?Recurrence $recurrence = null;

    #[ORM\OneToOne(targetEntity: InstallmentPlan::class, inversedBy: 'invoice', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private(set) ?InstallmentPlan $installmentPlan = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private(set) ?Uuid $recurrenceSeedId = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private(set) ?Uuid $installmentSeedId = null;

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function fromPayload(
        InvoicePayload $payload,
        Customer $customer,
        Company $company,
    ): self {
        $invoice = self::fromDocumentPayload($payload, $customer, $company);
        $invoice->dueDate = $payload->dueDate;

        return $invoice;
    }

    public function applyPayload(
        InvoicePayload $payload,
        Customer $customer,
        Company $company,
    ): void {
        if (InvoiceStatus::DRAFT !== $this->status) {
            throw new DocumentRuleViolationException('Only draft invoices can be updated.');
        }

        parent::applyDocumentPayload($payload, $customer, $company);

        $this->dueDate = $payload->dueDate;
    }

    // TRANSITIONS /////////////////////////////////////////////////////////////////////////////////////////////////////

    public function issue(\DateTimeImmutable $issuedAt, \DateTimeImmutable $dueDate): self
    {
        if (InvoiceStatus::DRAFT !== $this->status) {
            throw new DocumentTransitionException('Only draft invoices can be issued.');
        }

        if ($dueDate < $issuedAt) {
            throw new DocumentTransitionException('Due date must be on or after the issue date.');
        }

        $this->status = InvoiceStatus::ISSUED;
        $this->issuedAt = $issuedAt;
        $this->dueDate = $dueDate;

        return $this;
    }

    public function markOverdue(): self
    {
        if (InvoiceStatus::ISSUED !== $this->status) {
            throw new DocumentTransitionException('Only issued invoices can become overdue.');
        }

        $this->status = InvoiceStatus::OVERDUE;

        return $this;
    }

    public function markPaid(\DateTimeImmutable $paidAt): self
    {
        if (!in_array($this->status, [InvoiceStatus::ISSUED, InvoiceStatus::OVERDUE], true)) {
            throw new DocumentTransitionException('Only issued or overdue invoices can be marked as paid.');
        }

        if (null !== $this->issuedAt && $paidAt < $this->issuedAt) {
            throw new DocumentTransitionException('Payment date cannot precede the issue date.');
        }

        $this->status = InvoiceStatus::PAID;
        $this->paidAt = $paidAt;

        return $this;
    }

    public function void(): self
    {
        if (!in_array($this->status, [InvoiceStatus::DRAFT, InvoiceStatus::ISSUED], true)) {
            throw new DocumentTransitionException('Only draft or issued invoices can be voided.');
        }

        if (InvoiceStatus::ISSUED === $this->status && null !== $this->paidAt) {
            throw new DocumentTransitionException('Cannot void an invoice that has registered payments.');
        }

        $this->status = InvoiceStatus::VOIDED;
        $this->issuedAt = null;
        $this->dueDate = null;

        return $this;
    }

    public function revertToDraft(): self
    {
        if (InvoiceStatus::VOIDED !== $this->status) {
            throw new DocumentTransitionException('Only voided invoices can revert to draft.');
        }

        $this->status = InvoiceStatus::DRAFT;

        return $this;
    }

    // RECURRENCE //////////////////////////////////////////////////////////////////////////////////////////////////////

    public function attachRecurrence(Recurrence $recurrence): void
    {
        $this->assertNotGeneratedFromSeed();

        if ($this->hasRecurrence()) {
            throw new DocumentRuleViolationException('Invoice already has a recurrence configured.');
        } elseif ($this->hasInstallmentPlan()) {
            throw new DocumentRuleViolationException('Invoices cannot have both a recurrence and an installment plan.');
        }

        $this->recurrence = $recurrence;
    }

    public function updateRecurrence(RecurrencePayload $payload): void
    {
        if (false === $this->hasRecurrence()) {
            throw new DocumentRuleViolationException('Invoice does not have a recurrence configured.');
        }

        $this->recurrence->applyPayload($payload);
    }

    public function detachRecurrence(): void
    {
        if (false === $this->hasRecurrence()) {
            throw new DocumentRuleViolationException('Invoice does not have a recurrence configured.');
        }

        $this->recurrence = null;
    }

    private function hasRecurrence(): bool
    {
        return null !== $this->recurrence;
    }

    // INSTALLMENTS ////////////////////////////////////////////////////////////////////////////////////////////////////

    public function attachInstallmentPlan(InstallmentPlan $plan): void
    {
        $this->assertNotGeneratedFromSeed();

        if ($this->hasInstallmentPlan()) {
            throw new DocumentRuleViolationException('Invoice already has an installment plan.');
        } elseif ($this->hasRecurrence()) {
            throw new DocumentRuleViolationException('Invoices cannot have both an installment plan and a recurrence.');
        }

        $this->installmentPlan = $plan;
    }

    public function updateInstallmentPlan(InstallmentPlanPayload $payload): void
    {
        if (false === $this->hasInstallmentPlan()) {
            throw new DocumentRuleViolationException('Invoice does not have an installment plan.');
        }

        $this->installmentPlan->applyPayload($payload);
    }

    public function detachInstallmentPlan(): void
    {
        if (false === $this->hasInstallmentPlan()) {
            throw new DocumentRuleViolationException('Invoice does not have an installment plan.');
        }

        $this->installmentPlan->assertDetachable();
        $this->installmentPlan = null;
    }

    private function hasInstallmentPlan(): bool
    {
        return null !== $this->installmentPlan;
    }

    // SEED ////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function markGeneratedFromRecurrence(Uuid $seedId): void
    {
        $this->assertNotSeed();

        if (null !== $this->installmentSeedId) {
            throw new DocumentRuleViolationException('Invoices cannot be generated by both a recurrence and an installment plan.');
        }

        $this->recurrenceSeedId = $seedId;
    }

    public function markGeneratedFromInstallment(Uuid $seedId): void
    {
        $this->assertNotSeed();

        if (null !== $this->recurrenceSeedId) {
            throw new DocumentRuleViolationException('Invoices cannot be generated by both a recurrence and an installment plan.');
        }

        $this->installmentSeedId = $seedId;
    }

    private function assertNotSeed(): void
    {
        if ($this->hasRecurrence() || $this->hasInstallmentPlan()) {
            throw new DocumentRuleViolationException('Seed invoices cannot be generated from another seed.');
        }
    }

    private function assertNotGeneratedFromSeed(): void
    {
        if (null !== $this->recurrenceSeedId || null !== $this->installmentSeedId) {
            throw new DocumentRuleViolationException('Generated invoices cannot attach new scheduling rules.');
        }
    }
}
