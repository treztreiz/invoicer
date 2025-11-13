<?php

declare(strict_types=1);

namespace App\Application\UseCase\Document\Input;

use App\Domain\Enum\RateUnit;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DocumentLineInput
{
    public function __construct(
        #[Groups(['quote:write', 'invoice:write'])]
        #[Assert\NotBlank]
        private(set) string $description,

        #[Groups(['quote:write', 'invoice:write'])]
        #[Assert\Positive]
        private(set) float $quantity,

        #[Groups(['quote:write', 'invoice:write'])]
        #[Assert\Choice(callback: [RateUnit::class, 'rateUnits'])]
        private(set) string $rateUnit,

        #[Groups(['quote:write', 'invoice:write'])]
        #[Assert\PositiveOrZero]
        private(set) float $rate,

        #[Groups(['quote:write', 'invoice:write'])]
        #[Assert\NotBlank]
        #[Assert\Uuid(strict: true)]
        private(set) ?string $lineId = null,
    ) {
    }
}
