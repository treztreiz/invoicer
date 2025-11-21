<?php

declare(strict_types=1);

namespace App\Application\Dto\Invoice\Input\Installment;

use App\Application\Service\Transformer\InputTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

final class InstallmentInput
{
    public function __construct(
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Uuid(strict: true)]
        #[Map(target: 'id', transform: [InputTransformer::class, 'uuid'])]
        private(set) ?string $installmentId {
            get => $this->installmentId ?? null;
            set => $value;
        },

        #[Assert\Positive]
        #[Assert\Range(min: 1, max: 100)]
        #[Map(transform: [InputTransformer::class, 'percentage'])]
        private(set) readonly float $percentage,

        #[Map(transform: [InputTransformer::class, 'dateOptional'])]
        private(set) readonly ?string $dueDate = null,
    ) {
    }
}
