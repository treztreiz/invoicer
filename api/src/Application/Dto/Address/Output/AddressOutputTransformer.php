<?php

declare(strict_types=1);

namespace App\Application\Dto\Address\Output;

use App\Application\Dto\Customer\Output\CustomerOutput;
use App\Application\Dto\User\Output\UserOutput;
use App\Application\Service\Trait\ObjectMapperAwareTrait;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\User\User;
use App\Domain\ValueObject\Address;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<Customer|User, CustomerOutput|UserOutput> */
final class AddressOutputTransformer implements TransformCallableInterface
{
    use ObjectMapperAwareTrait;

    /** @param Address $value */
    public function __invoke(mixed $value, object $source, ?object $target): AddressOutput
    {
        return $this->objectMapper->map($value, AddressOutput::class);
    }
}
