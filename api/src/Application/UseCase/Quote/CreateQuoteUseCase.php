<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote;

use App\Application\Dto\Quote\Input\QuoteInput;
use App\Application\Dto\Quote\Output\QuoteOutput;
use App\Application\Service\Trait\CustomerRepositoryAwareTrait;
use App\Application\Service\Trait\DocumentSnapshotFactoryAwareTrait;
use App\Application\Service\Trait\QuoteRepositoryAwareTrait;
use App\Application\Service\Trait\UserRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Quote;
use App\Domain\Entity\User\User;
use App\Domain\Payload\Document\QuotePayload;

final class CreateQuoteUseCase extends AbstractUseCase
{
    use CustomerRepositoryAwareTrait;
    use DocumentSnapshotFactoryAwareTrait;
    use UserRepositoryAwareTrait;
    use QuoteRepositoryAwareTrait;

    public function handle(QuoteInput $input, string $userId): QuoteOutput
    {
        $user = $this->findOneById($this->userRepository, $userId, User::class);
        $customer = $this->findOneById($this->customerRepository, $input->customerId, Customer::class);

        $payload = $this->map($input, QuotePayload::class);

        $quote = Quote::fromPayload(
            payload: $payload,
            customer: $customer,
            customerSnapshot: $this->documentSnapshotFactory->customerSnapshot($customer),
            companySnapshot: $this->documentSnapshotFactory->companySnapshot($user)
        );

        $this->quoteRepository->save($quote);

        return $this->map($quote, QuoteOutput::class);
    }
}
