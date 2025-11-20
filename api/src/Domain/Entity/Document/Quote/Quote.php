<?php

declare(strict_types=1);

namespace App\Domain\Entity\Document\Quote;

use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Document;
use App\Domain\Enum\QuoteStatus;
use App\Domain\Exception\DocumentRuleViolationException;
use App\Domain\Exception\DocumentTransitionException;
use App\Domain\Payload\Quote\QuotePayload;
use App\Infrastructure\Doctrine\CheckAware\Attribute\EnumCheck;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-import-type CustomerSnapshot from Document
 * @phpstan-import-type CompanySnapshot from Document
 */
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

    /**
     * @param CustomerSnapshot $customerSnapshot
     * @param CompanySnapshot  $companySnapshot
     */
    public static function fromPayload(
        QuotePayload $payload,
        Customer $customer,
        array $customerSnapshot,
        array $companySnapshot,
    ): self {
        return self::fromDocumentPayload($payload, $customer, $customerSnapshot, $companySnapshot);
    }

    /**
     * @param CustomerSnapshot $customerSnapshot
     * @param CompanySnapshot  $companySnapshot
     */
    public function applyPayload(
        QuotePayload $payload,
        Customer $customer,
        array $customerSnapshot,
        array $companySnapshot,
    ): void {
        if (QuoteStatus::DRAFT !== $this->status) {
            throw new DocumentRuleViolationException('Only draft quotes can be updated.');
        }

        parent::applyDocumentPayload($payload, $customer, $customerSnapshot, $companySnapshot);
    }

    // TRANSITIONS /////////////////////////////////////////////////////////////////////////////////////////////////////

    public function send(\DateTimeImmutable $sentAt): self
    {
        if (QuoteStatus::DRAFT !== $this->status) {
            throw new DocumentTransitionException('Only draft quotes can be sent.');
        }

        $this->status = QuoteStatus::SENT;
        $this->sentAt = $sentAt;

        return $this;
    }

    public function markAccepted(\DateTimeImmutable $acceptedAt): self
    {
        if (QuoteStatus::SENT !== $this->status) {
            throw new DocumentTransitionException('Only sent quotes can be accepted.');
        }

        $this->status = QuoteStatus::ACCEPTED;
        $this->acceptedAt = $acceptedAt;
        $this->rejectedAt = null;

        return $this;
    }

    public function markRejected(\DateTimeImmutable $rejectedAt): self
    {
        if (QuoteStatus::SENT !== $this->status) {
            throw new DocumentTransitionException('Only sent quotes can be rejected.');
        }

        $this->status = QuoteStatus::REJECTED;
        $this->rejectedAt = $rejectedAt;
        $this->acceptedAt = null;

        return $this;
    }

    public function linkConvertedInvoice(Uuid $invoiceId): self
    {
        if (QuoteStatus::ACCEPTED !== $this->status) {
            throw new DocumentRuleViolationException('Only accepted quotes can be converted to an invoice.');
        }

        $this->convertedInvoiceId = $invoiceId;

        return $this;
    }
}
