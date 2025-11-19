<?php

declare(strict_types=1);

namespace App\Domain\Entity\Document\Invoice;

use App\Domain\Entity\Common\UuidTrait;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;
use App\Domain\Guard\DomainGuard;
use App\Domain\Payload\Document\Invoice\InvoiceRecurrencePayload;
use App\Infrastructure\Doctrine\CheckAware\Attribute\EnumCheck;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invoice_recurrence')]
#[EnumCheck(property: 'frequency', name: 'CHK_RECURRENCE_FREQUENCY')]
#[EnumCheck(property: 'endStrategy', name: 'CHK_RECURRENCE_END_STRATEGY')]
class InvoiceRecurrence
{
    use UuidTrait;

    #[ORM\OneToOne(targetEntity: Invoice::class, mappedBy: 'recurrence')]
    private(set) ?Invoice $invoice = null;

    public function __construct(
        #[ORM\Column(enumType: RecurrenceFrequency::class)]
        private(set) readonly RecurrenceFrequency $frequency,

        #[ORM\Column(type: Types::SMALLINT)]
        private(set) int $interval {
            set => DomainGuard::positiveInt($value, 'Recurrence interval');
        },

        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        private(set) readonly \DateTimeImmutable $anchorDate,

        #[ORM\Column(enumType: RecurrenceEndStrategy::class)]
        private(set) readonly RecurrenceEndStrategy $endStrategy = RecurrenceEndStrategy::UNTIL_DATE,

        #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
        private(set) readonly ?\DateTimeImmutable $endDate = null,

        #[ORM\Column(type: Types::SMALLINT, nullable: true)]
        private(set) readonly ?int $occurrenceCount = null,

        #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
        private(set) readonly ?\DateTimeImmutable $nextRunAt = null,
    ) {
        $propertyRequirements = match ($endStrategy) {
            RecurrenceEndStrategy::NEVER => ['endDate' => false, 'occurrenceCount' => false],
            RecurrenceEndStrategy::UNTIL_DATE => ['endDate' => true, 'occurrenceCount' => false],
            RecurrenceEndStrategy::UNTIL_COUNT => ['endDate' => false, 'occurrenceCount' => true],
        };

        $this->assertEndStrategyProperties($propertyRequirements);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function fromPayload(InvoiceRecurrencePayload $payload): self
    {
        return new self(
            frequency: $payload->frequency,
            interval: $payload->interval,
            anchorDate: $payload->anchorDate,
            endStrategy: $payload->endStrategy,
            endDate: $payload->endDate,
            occurrenceCount: $payload->occurrenceCount,
        );
    }

    /**
     * @param array<string, mixed> $propertyRequirements
     */
    private function assertEndStrategyProperties(array $propertyRequirements): void
    {
        if (!isset($propertyRequirements['endDate']) || !is_bool($propertyRequirements['endDate'])) {
            throw new \InvalidArgumentException('Key "endDate" must be a boolean.');
        } elseif (!isset($propertyRequirements['occurrenceCount']) || !is_bool($propertyRequirements['occurrenceCount'])) {
            throw new \InvalidArgumentException('Key "occurrenceCount" must be a boolean.');
        }

        foreach ($propertyRequirements as $propertyName => $required) {
            if (false === property_exists($this, $propertyName)) {
                throw new \InvalidArgumentException(sprintf('Property "%s" does not exist.', $propertyName));
            }

            $isPropertySet = $this->$propertyName !== null;

            if ($required && false === $isPropertySet) {
                throw new \InvalidArgumentException(sprintf('"%s" is required when end strategy is "%s".', $propertyName, $this->endStrategy->name));
            } elseif (false === $required && $isPropertySet) {
                throw new \InvalidArgumentException(sprintf('"%s" must not be set when end strategy is "%s".', $propertyName, $this->endStrategy->name));
            }
        }
    }
}
