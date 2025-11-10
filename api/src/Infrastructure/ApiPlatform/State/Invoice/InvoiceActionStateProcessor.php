<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Invoice;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Command\InvoiceActionCommand;
use App\Application\UseCase\Invoice\Handler\InvoiceActionHandler;
use App\Application\UseCase\Invoice\Input\InvoiceActionInput;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;

/**
 * @implements ProcessorInterface<InvoiceActionInput, InvoiceOutput>
 */
final readonly class InvoiceActionStateProcessor implements ProcessorInterface
{
    public function __construct(
        private InvoiceActionHandler $handler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): InvoiceOutput
    {
        $input = TypeGuard::assertClass(InvoiceActionInput::class, $data);
        $invoiceId = (string) ($uriVariables['id'] ?? '');

        if ('' === $invoiceId) {
            throw new \InvalidArgumentException('Invoice id is required.');
        }

        $command = new InvoiceActionCommand($invoiceId, $input->action);

        return $this->handler->handle($command);
    }
}
