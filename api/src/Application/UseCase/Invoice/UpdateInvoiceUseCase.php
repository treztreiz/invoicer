<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice;

use App\Application\Dto\Invoice\Input\InvoiceInput;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\Service\Trait\CustomerRepositoryAwareTrait;
use App\Application\Service\Trait\InvoiceRepositoryAwareTrait;
use App\Application\Service\Trait\UserRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Invoice\Invoice;
use App\Domain\Entity\User\User;

final class UpdateInvoiceUseCase extends AbstractUseCase
{
    use CustomerRepositoryAwareTrait;
    use InvoiceRepositoryAwareTrait;
    use UserRepositoryAwareTrait;

    public function handle(InvoiceInput $input, string $invoiceId, string $userId): InvoiceOutput
    {
        $invoice = $this->findOneById($this->invoiceRepository, $invoiceId, Invoice::class);
        $user = $this->findOneById($this->userRepository, $userId, User::class);
        $customer = $this->findOneById($this->customerRepository, $input->customerId, Customer::class);

        $payload = $this->map($input, \App\Domain\Payload\Invoice\InvoicePayload::class);

        $invoice->applyPayload(
            payload: $payload,
            customer: $customer,
            company: $user->company
        );

        $this->invoiceRepository->save($invoice);

        return $this->map($invoice, InvoiceOutput::class);
    }
}
