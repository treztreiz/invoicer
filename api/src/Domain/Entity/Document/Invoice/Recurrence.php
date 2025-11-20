<?php

declare(strict_types=1);

namespace App\Domain\Entity\Document\Invoice;

use App\Domain\Entity\Common\UuidTrait;
use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;
use App\Domain\Guard\DomainGuard;
use App\Domain\Payload\Invoice\Recurrence\RecurrencePayload;
use App\Infrastructure\Doctrine\CheckAware\Attribute\EnumCheck;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'recurrence')]
#[EnumCheck(property: 'frequency', name: 'CHK_RECURRENCE_FREQUENCY')]
#[EnumCheck(property: 'endStrategy', name: 'CHK_RECURRENCE_END_STRATEGY')]
class Recurrence
{
    use UuidTrait;

    #[ORM\OneToOne(targetEntity: Invoice::class, mappedBy: 'recurrence')]
    private(set) ?Invoice $invoice = null;

    private function __construct(
        #[ORM\Column(enumType: RecurrenceFrequency::class)]
        private(set) RecurrenceFrequency $frequency,

        #[ORM\Column(type: Types::SMALLINT)]
        private(set) int $interval {
            set => DomainGuard::positiveInt($value, 'Recurrence interval');
        },

        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        private(set) \DateTimeImmutable $anchorDate,

        #[ORM\Column(enumType: RecurrenceEndStrategy::class)]
        private(set) RecurrenceEndStrategy $endStrategy = RecurrenceEndStrategy::UNTIL_DATE,

        #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
        private(set) ?\DateTimeImmutable $endDate = null,

        #[ORM\Column(type: Types::SMALLINT, nullable: true)]
        private(set) ?int $occurrenceCount = null,

        #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
        private(set) readonly ?\DateTimeImmutable $nextRunAt = null,
    ) {
        $this->assertPropertiesMatchStrategy();
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function fromPayload(RecurrencePayload $payload): self
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

    public function applyPayload(RecurrencePayload $payload): void
    {
        $this->frequency = $payload->frequency;
        $this->interval = $payload->interval;
        $this->anchorDate = $payload->anchorDate;
        $this->endStrategy = $payload->endStrategy;
        $this->endDate = $payload->endDate;
        $this->occurrenceCount = $payload->occurrenceCount;
        $this->assertPropertiesMatchStrategy();
    }

    // GUARDS //////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function assertPropertiesMatchStrategy(): void
    {
        $strategyProperties = match ($this->endStrategy) {
            RecurrenceEndStrategy::NEVER => ['endDate' => false, 'occurrenceCount' => false],
            RecurrenceEndStrategy::UNTIL_DATE => ['endDate' => true, 'occurrenceCount' => false],
            RecurrenceEndStrategy::UNTIL_COUNT => ['endDate' => false, 'occurrenceCount' => true],
        };

        foreach ($strategyProperties as $propertyName => $required) {
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
