<?php

declare(strict_types=1);

namespace App\Domain\Entity\Document\Invoice;

use App\Domain\Entity\Common\UuidTrait;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;
use App\Domain\Guard\DomainGuard;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invoice_recurrence')]
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

        #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
        private(set) readonly ?\DateTimeImmutable $nextRunAt = null,

        #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
        private(set) readonly ?\DateTimeImmutable $endDate = null,

        #[ORM\Column(type: Types::SMALLINT, nullable: true)]
        private(set) readonly ?int $occurrenceCount = null,
    ) {
    }
}
