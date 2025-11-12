<?php

declare(strict_types=1);

namespace App\Application\UseCase\Document\Input;

use App\Domain\Enum\RateUnit;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class DocumentLineInput
{
    public function __construct(
        #[Groups(['quote:write', 'invoice:write'])]
        #[Assert\NotBlank]
        public string $description,

        #[Groups(['quote:write', 'invoice:write'])]
        #[Assert\Positive]
        public float $quantity,

        #[Groups(['quote:write', 'invoice:write'])]
        #[Assert\Choice(callback: [self::class, 'availableRateUnits'])]
        public string $rateUnit,

        #[Groups(['quote:write', 'invoice:write'])]
        #[Assert\PositiveOrZero]
        public float $rate,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function availableRateUnits(): array
    {
        return array_map(static fn (RateUnit $unit) => $unit->value, RateUnit::cases());
    }
}
