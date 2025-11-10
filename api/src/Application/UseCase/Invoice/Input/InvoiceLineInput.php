<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Input;

use App\Domain\Enum\RateUnit;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class InvoiceLineInput
{
    public function __construct(
        #[Groups(['invoice:write'])]
        #[Assert\NotBlank]
        public string $description,

        #[Groups(['invoice:write'])]
        #[Assert\Positive]
        public float $quantity,

        #[Groups(['invoice:write'])]
        #[Assert\Choice(callback: [self::class, 'availableUnits'])]
        public string $rateUnit,

        #[Groups(['invoice:write'])]
        #[Assert\PositiveOrZero]
        public float $rate,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function availableUnits(): array
    {
        return array_map(static fn (RateUnit $unit) => $unit->value, RateUnit::cases());
    }
}
