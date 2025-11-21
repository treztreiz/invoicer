<?php

declare(strict_types=1);

namespace App\Application\Dto\User\Output;

use App\Application\Guard\TypeGuard;
use App\Domain\Entity\User\User;
use App\Domain\ValueObject\CompanyLogo;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<User, UserOutput> */
readonly class UserOutputLogoTransformer implements TransformCallableInterface
{
    public function __construct(
        #[Autowire(param: 'app.company_logo_base_url')]
        private string $baseUrl,
    ) {
    }

    /** @param CompanyLogo $value */
    public function __invoke(mixed $value, object $source, ?object $target): ?string
    {
        $logo = TypeGuard::assertClass(CompanyLogo::class, $value);

        return $logo->url($this->baseUrl);
    }
}
