<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Invoice;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Command\AttachInvoiceRecurrenceCommand;
use App\Application\UseCase\Invoice\Handler\AttachInvoiceRecurrenceHandler;
use App\Application\UseCase\Invoice\Input\InvoiceRecurrenceInput;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;

/**
 * @implements ProcessorInterface<InvoiceRecurrenceInput, InvoiceOutput>
 */
final readonly class InvoiceRecurrenceStateProcessor implements ProcessorInterface
{
    public function __construct(private AttachInvoiceRecurrenceHandler $handler)
    {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): InvoiceOutput
    {
        $input = TypeGuard::assertClass(InvoiceRecurrenceInput::class, $data);
        $invoiceId = (string) ($uriVariables['id'] ?? '');

        if ('' === $invoiceId) {
            throw new \InvalidArgumentException('Invoice id is required.');
        }

        return $this->handler->handle(new AttachInvoiceRecurrenceCommand($invoiceId, $input));
    }
}
