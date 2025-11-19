<?php

declare(strict_types=1);

namespace App\Application\Dto\Address\Input;

use App\Application\Dto\Customer\Input\CustomerInput;
use App\Application\Dto\User\Input\UserInput;
use App\Application\Guard\TypeGuard;
use App\Application\Service\Trait\ObjectMapperAwareTrait;
use App\Domain\Payload\Customer\CustomerPayload;
use App\Domain\Payload\User\UserPayload;
use App\Domain\ValueObject\Address;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<CustomerInput|UserInput, CustomerPayload|UserPayload> */
class AddressInputTransformer implements TransformCallableInterface
{
    use ObjectMapperAwareTrait;

    /** @param AddressInput $value */
    public function __invoke(mixed $value, object $source, ?object $target): Address
    {
        $addressInput = TypeGuard::assertClass(AddressInput::class, $value);

        return $this->objectMapper->map($addressInput, Address::class);
    }
}
