<?php

declare(strict_types=1);

namespace App\Application\Dto\Document\Input;

use App\Application\Dto\Invoice\Input\InvoiceInput;
use App\Application\Dto\Quote\Input\QuoteInput;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Service\Trait\CustomerRepositoryAwareTrait;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Exception\DocumentRuleViolationException;
use App\Domain\Payload\Invoice\InvoicePayload;
use App\Domain\Payload\Quote\QuotePayload;
use Symfony\Component\ObjectMapper\TransformCallableInterface;
use Symfony\Component\Uid\Uuid;

/** @implements TransformCallableInterface<InvoiceInput|QuoteInput, InvoicePayload|QuotePayload> */
final class DocumentCustomerInputTransformer implements TransformCallableInterface
{
    use CustomerRepositoryAwareTrait;

    /** @param string $value */
    public function __invoke(mixed $value, object $source, ?object $target): Customer
    {
        if (false === Uuid::isValid($value)) {
            throw new DocumentRuleViolationException('Value must be a valid UUID.');
        }

        $customer = $this->customerRepository->findOneById(Uuid::fromString($value));
        if (null === $customer) {
            throw new ResourceNotFoundException(Customer::class, $value);
        }

        return $customer;
    }
}
