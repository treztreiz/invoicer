<?php

namespace App\Domain\Entity\Document;

use App\Domain\Enum\InvoiceStatus;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use LogicException;

#[ORM\Entity]
#[ORM\Table(name: 'invoice')]
final class Invoice extends Document
{
    #[ORM\Column]
    private(set) InvoiceStatus $status = InvoiceStatus::DRAFT;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $issuedAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $dueDate = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $paidAt = null;

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function issue(DateTimeImmutable $issuedAt, DateTimeImmutable $dueDate): self
    {
        if (InvoiceStatus::DRAFT !== $this->status) {
            throw new LogicException('Only draft invoices can be issued.');
        }

        if ($dueDate < $issuedAt) {
            throw new LogicException('Due date must be on or after the issue date.');
        }

        $this->status = InvoiceStatus::ISSUED;
        $this->issuedAt = $issuedAt;
        $this->dueDate = $dueDate;

        return $this;
    }

    public function markOverdue(): self
    {
        if (InvoiceStatus::ISSUED !== $this->status) {
            throw new LogicException('Only issued invoices can become overdue.');
        }

        $this->status = InvoiceStatus::OVERDUE;

        return $this;
    }

    public function markPaid(DateTimeImmutable $paidAt): self
    {
        if (!in_array($this->status, [InvoiceStatus::ISSUED, InvoiceStatus::OVERDUE], true)) {
            throw new LogicException('Only issued or overdue invoices can be marked as paid.');
        }

        if (null !== $this->issuedAt && $paidAt < $this->issuedAt) {
            throw new LogicException('Payment date cannot precede the issue date.');
        }

        $this->status = InvoiceStatus::PAID;
        $this->paidAt = $paidAt;

        return $this;
    }

    public function void(): self
    {
        if (!in_array($this->status, [InvoiceStatus::DRAFT, InvoiceStatus::ISSUED], true)) {
            throw new LogicException('Only draft or issued invoices can be voided.');
        }

        if (InvoiceStatus::ISSUED === $this->status && null !== $this->paidAt) {
            throw new LogicException('Cannot void an invoice that has registered payments.');
        }

        $this->status = InvoiceStatus::VOIDED;
        $this->issuedAt = null;
        $this->dueDate = null;

        return $this;
    }

    public function revertToDraft(): self
    {
        if (InvoiceStatus::VOIDED !== $this->status) {
            throw new LogicException('Only voided invoices can revert to draft.');
        }

        $this->status = InvoiceStatus::DRAFT;

        return $this;
    }
}
