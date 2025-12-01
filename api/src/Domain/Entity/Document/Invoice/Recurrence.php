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

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private(set) ?\DateTimeImmutable $nextRunAt = null;

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
        private(set) ?int $occurrenceCount = null {
            set => DomainGuard::optionalPositiveInt($value, 'Occurrence count');
        },
    ) {
        $this->assertPropertiesMatchStrategy();
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function fromPayload(RecurrencePayload $payload): self
    {
        $recurrence = new self(
            frequency: $payload->frequency,
            interval: $payload->interval,
            anchorDate: $payload->anchorDate,
            endStrategy: $payload->endStrategy,
            endDate: $payload->endDate,
            occurrenceCount: $payload->occurrenceCount,
        );
        $recurrence->updateNextRunAt();

        return $recurrence;
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
        $this->updateNextRunAt();
    }

    public function isRunnable(bool $allowBeforeNextRun = false): bool
    {
        $today = new \DateTimeImmutable('today');
        $strategy = $this->endStrategy;

        if (
            (RecurrenceEndStrategy::UNTIL_COUNT === $strategy && $this->occurrenceCount <= 0)
            || (RecurrenceEndStrategy::UNTIL_DATE === $strategy && $this->endDate <= $today)
            || null === $this->nextRunAt
        ) {
            return false;
        }

        if (false === $allowBeforeNextRun) {
            return $this->nextRunAt <= $today;
        }

        $months = $this->frequency->asMonth() * $this->interval;
        $minimalRunDate = $this->nextRunAt->sub(new \DateInterval(sprintf('P%sM', $months)));

        return $minimalRunDate <= $today;
    }

    public function updateNextRunAt(): void
    {
        if (null === $this->nextRunAt) {
            $this->nextRunAt = $this->anchorDate;

            return;
        }

        $months = $this->frequency->asMonth() * $this->interval;
        $next = $this->nextRunAt->add(new \DateInterval(sprintf('P%dM', $months)));

        if (RecurrenceEndStrategy::UNTIL_COUNT === $this->endStrategy) {
            $remaining = $this->occurrenceCount ?? 1;
            --$remaining;
            $this->occurrenceCount = $remaining;

            if ($remaining <= 0) {
                $this->nextRunAt = null;

                return;
            }
        }

        if (
            RecurrenceEndStrategy::UNTIL_DATE === $this->endStrategy
            && null !== $this->endDate
            && $next > $this->endDate
        ) {
            $this->nextRunAt = null;

            return;
        }

        $this->nextRunAt = $next;
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
