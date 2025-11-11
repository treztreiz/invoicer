<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Invoice;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Handler\InvoiceActionHandler;
use App\Application\UseCase\Invoice\Input\InvoiceActionInput;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Task\InvoiceActionTask;

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
        $invoiceId = (string)($uriVariables['invoiceId'] ?? '');

        if ('' === $invoiceId) {
            throw new \InvalidArgumentException('Invoice id is required.');
        }

        $task = new InvoiceActionTask($invoiceId, $input->action);

        return $this->handler->handle($task);
    }
}
