<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Invoice\Recurrence;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Dto\Invoice\Input\Recurrence\InvoiceRecurrenceInput;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Recurrence\DetachInvoiceRecurrenceUseCase;

/**
 * @implements ProcessorInterface<InvoiceRecurrenceInput, InvoiceOutput>
 */
final readonly class DetachInvoiceRecurrenceProcessor implements ProcessorInterface
{
    public function __construct(private DetachInvoiceRecurrenceUseCase $handler)
    {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): InvoiceOutput
    {
        $invoiceId = (string) ($uriVariables['invoiceId'] ?? '');

        if ('' === $invoiceId) {
            throw new \InvalidArgumentException('Invoice id is required.');
        }

        return $this->handler->handle($invoiceId);
    }
}
