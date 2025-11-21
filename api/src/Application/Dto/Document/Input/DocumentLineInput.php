<?php

declare(strict_types=1);

namespace App\Application\Dto\Document\Input;

use App\Application\Service\Transformer\InputTransformer;
use App\Domain\Enum\RateUnit;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

final class DocumentLineInput
{
    public function __construct(
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Uuid(strict: true)]
        #[Map(target: 'id', transform: [InputTransformer::class, 'uuid'])]
        private(set) ?string $lineId {
            get => $this->lineId ?? null;
            set => $value;
        },

        #[Assert\NotBlank]
        private(set) readonly string $description,

        #[Assert\Positive]
        #[Map(transform: [InputTransformer::class, 'quantity'])]
        private(set) readonly float $quantity,

        #[Assert\Choice(callback: [RateUnit::class, 'rateUnits'])]
        #[Map(transform: [InputTransformer::class, 'rateUnit'])]
        private(set) readonly string $rateUnit,

        #[Assert\PositiveOrZero]
        #[Map(transform: [InputTransformer::class, 'money'])]
        private(set) readonly float $rate,
    ) {
    }
}
