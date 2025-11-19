<?php

declare(strict_types=1);

namespace App\Application\Dto\User\Input;

use App\Application\Guard\TypeGuard;
use App\Application\Service\Trait\ObjectMapperAwareTrait;
use App\Domain\Payload\User\UserPayload;
use App\Domain\ValueObject\Company;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<UserInput, UserPayload> */
final class CompanyInputTransformer implements TransformCallableInterface
{
    use ObjectMapperAwareTrait;

    /** @param CompanyInput $value */
    public function __invoke(mixed $value, object $source, ?object $target): Company
    {
        $companyInput = TypeGuard::assertClass(CompanyInput::class, $value);

        return $this->objectMapper->map($companyInput, Company::class);
    }
}
