<?php

declare(strict_types=1);

namespace App\Application\Dto\Document\Input;

use App\Domain\Enum\RateUnit;
use Symfony\Component\Validator\Constraints as Assert;

final class DocumentLineInput
{
    public function __construct(
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Uuid(strict: true)]
        private(set) ?string $lineId {
            get => $this->lineId ?? null;
            set => $value;
        },

        #[Assert\NotBlank]
        private(set) readonly string $description,

        #[Assert\Positive]
        private(set) readonly float $quantity,

        #[Assert\Choice(callback: [RateUnit::class, 'rateUnits'])]
        private(set) readonly string $rateUnit,

        #[Assert\PositiveOrZero]
        private(set) readonly float $rate,
    ) {
    }
}
