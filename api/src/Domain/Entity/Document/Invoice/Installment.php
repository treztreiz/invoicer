<?php

declare(strict_types=1);

namespace App\Domain\Entity\Document\Invoice;

use App\Domain\Entity\Common\UuidTrait;
use App\Domain\Guard\DomainGuard;
use App\Domain\Payload\Document\Invoice\AllocatedInstallmentPayload;
use App\Domain\ValueObject\AmountBreakdown;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'installment')]
class Installment
{
    use UuidTrait;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private(set) ?Uuid $generatedInvoiceId = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: InstallmentPlan::class, inversedBy: 'installments')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private(set) readonly InstallmentPlan $installmentPlan,

        #[ORM\Column(Types::INTEGER)]
        private(set) int $position {
            set => DomainGuard::nonNegativeInt($value, 'Position');
        },

        #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
        private(set) string $percentage {
            set => DomainGuard::decimal($value, 2, 'Installment percentage', false, 0.0, 100.0);
        },

        #[ORM\Embedded]
        private(set) AmountBreakdown $amount,

        #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
        private(set) ?\DateTimeImmutable $dueDate = null,
    ) {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function fromPayload(AllocatedInstallmentPayload $payload, InstallmentPlan $installmentPlan): self
    {
        return new self(
            installmentPlan: $installmentPlan,
            position: $payload->position,
            percentage: $payload->percentage,
            amount: $payload->amount,
            dueDate: $payload->dueDate,
        );
    }

    public function applyPayload(AllocatedInstallmentPayload $payload): void
    {
        $this->assertMutable();
        $this->position = $payload->position;
        $this->percentage = $payload->percentage;
        $this->amount = $payload->amount;
        $this->dueDate = $payload->dueDate;
    }

    public function assertMutable(): void
    {
        if (null !== $this->generatedInvoiceId) {
            throw new \LogicException('Generated installments cannot be modified or removed.');
        }
    }
}
