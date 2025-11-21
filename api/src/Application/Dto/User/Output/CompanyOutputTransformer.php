<?php

declare(strict_types=1);

namespace App\Application\Dto\User\Output;

use App\Application\Guard\TypeGuard;
use App\Application\Service\Trait\ObjectMapperAwareTrait;
use App\Domain\Entity\User\User;
use App\Domain\ValueObject\Company;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<User, UserOutput> */
final class CompanyOutputTransformer implements TransformCallableInterface
{
    use ObjectMapperAwareTrait;

    /** @param Company $value */
    public function __invoke(mixed $value, object $source, ?object $target): CompanyOutput
    {
        $company = TypeGuard::assertClass(Company::class, $value);

        return $this->objectMapper->map($company, CompanyOutput::class);
    }
}
