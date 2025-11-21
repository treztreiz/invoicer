<?php

declare(strict_types=1);

namespace App\Application\Dto\Address\Output;

final readonly class AddressOutput
{
    public function __construct(
        private(set) string $streetLine1,

        private(set) ?string $streetLine2,

        private(set) string $postalCode,

        private(set) string $city,

        private(set) ?string $region,

        private(set) string $countryCode,
    ) {
    }
}
