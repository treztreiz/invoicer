<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Input;

use App\Domain\Enum\RateUnit;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class QuoteLineInput
{
    public function __construct(
        #[Groups(['quote:write'])]
        #[Assert\NotBlank]
        public string $description,

        #[Groups(['quote:write'])]
        #[Assert\Positive]
        public float $quantity,

        #[Groups(['quote:write'])]
        #[Assert\Choice(callback: [self::class, 'availableRateUnits'])]
        public string $rateUnit,

        #[Groups(['quote:write'])]
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
