<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice;

use App\Application\Dto\Invoice\Input\InvoiceInput;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\Service\Trait\InvoiceRepositoryAwareTrait;
use App\Application\Service\Trait\UserRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\Document\Invoice\Invoice;
use App\Domain\Entity\User\User;
use App\Domain\Payload\Invoice\InvoicePayload;

final class CreateInvoiceUseCase extends AbstractUseCase
{
    use InvoiceRepositoryAwareTrait;
    use UserRepositoryAwareTrait;

    public function handle(InvoiceInput $input, string $userId): InvoiceOutput
    {
        $user = $this->findOneById($this->userRepository, $userId, User::class);

        $payload = $this->map($input, InvoicePayload::class);

        $invoice = Invoice::fromPayload(
            payload: $payload,
            customer: $payload->customer,
            company: $user->company
        );

        $this->invoiceRepository->save($invoice);

        return $this->map($invoice, InvoiceOutput::class);
    }
}
