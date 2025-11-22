<?php

declare(strict_types=1);

namespace App\Application\Dto\User\Output;

use App\Application\Service\Transformer\OutputTransformer;
use App\Domain\Entity\User\User;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(source: User::class)]
final readonly class UserOutput
{
    /** @param array<int, string> $roles */
    public function __construct(
        #[Map(source: 'id', transform: [OutputTransformer::class, 'uuid'])]
        private(set) string $userId,

        #[Map(source: 'name.firstName')]
        private(set) string $firstName,

        #[Map(source: 'name.lastName')]
        private(set) string $lastName,

        #[Map(source: 'contact', transform: UserOutputEmailTransformer::class)]
        private(set) string $email,

        #[Map(source: 'contact.phone')]
        private(set) ?string $phone,

        private(set) string $locale,

        private(set) array $roles,

        #[Map(transform: CompanyOutputTransformer::class)]
        private(set) CompanyOutput $company,

        #[Map(source: 'companyLogo', transform: UserOutputLogoTransformer::class)]
        private(set) ?string $logoUrl,
    ) {
    }
}
