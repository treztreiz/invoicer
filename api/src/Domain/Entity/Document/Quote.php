<?php

declare(strict_types=1);

namespace App\Domain\Entity\Document;

use App\Domain\DTO\QuotePayload;
use App\Domain\Enum\QuoteStatus;
use App\Infrastructure\Doctrine\CheckAware\Attribute\EnumCheck;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'quote')]
#[EnumCheck(property: 'status', name: 'CHK_QUOTE_STATUS')]
class Quote extends Document
{
    #[ORM\Column(enumType: QuoteStatus::class)]
    private(set) QuoteStatus $status = QuoteStatus::DRAFT;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private(set) ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private(set) ?\DateTimeImmutable $acceptedAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private(set) ?\DateTimeImmutable $rejectedAt = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private(set) ?Uuid $convertedInvoiceId = null;

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function fromPayload(QuotePayload $payload): self
    {
        $quote = new self(
            title: $payload->title,
            currency: $payload->currency,
            vatRate: $payload->vatRate,
            total: $payload->total,
            customerSnapshot: $payload->customerSnapshot,
            companySnapshot: $payload->companySnapshot,
            subtitle: $payload->subtitle,
        );

        foreach ($payload->lines as $linePayload) {
            $quote->addLine($linePayload);
        }

        return $quote;
    }

    public function send(\DateTimeImmutable $sentAt): self
    {
        if (QuoteStatus::DRAFT !== $this->status) {
            throw new \LogicException('Only draft quotes can be sent.');
        }

        $this->status = QuoteStatus::SENT;
        $this->sentAt = $sentAt;

        return $this;
    }

    public function markAccepted(\DateTimeImmutable $acceptedAt): self
    {
        if (QuoteStatus::SENT !== $this->status) {
            throw new \LogicException('Only sent quotes can be accepted.');
        }

        $this->status = QuoteStatus::ACCEPTED;
        $this->acceptedAt = $acceptedAt;
        $this->rejectedAt = null;

        return $this;
    }

    public function markRejected(\DateTimeImmutable $rejectedAt): self
    {
        if (QuoteStatus::SENT !== $this->status) {
            throw new \LogicException('Only sent quotes can be rejected.');
        }

        $this->status = QuoteStatus::REJECTED;
        $this->rejectedAt = $rejectedAt;
        $this->acceptedAt = null;

        return $this;
    }

    public function linkConvertedInvoice(Uuid $invoiceId): self
    {
        if (QuoteStatus::ACCEPTED !== $this->status) {
            throw new \LogicException('Only accepted quotes can be converted to an invoice.');
        }

        $this->convertedInvoiceId = $invoiceId;

        return $this;
    }
}
