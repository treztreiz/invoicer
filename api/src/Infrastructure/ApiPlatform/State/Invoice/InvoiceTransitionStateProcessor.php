<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Invoice;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Handler\InvoiceTransitionHandler;
use App\Application\UseCase\Invoice\Input\InvoiceTransitionInput;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Task\InvoiceTransitionTask;

/**
 * @implements ProcessorInterface<InvoiceTransitionInput, InvoiceOutput>
 */
final readonly class InvoiceTransitionStateProcessor implements ProcessorInterface
{
    public function __construct(
        private InvoiceTransitionHandler $handler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): InvoiceOutput
    {
        $input = TypeGuard::assertClass(InvoiceTransitionInput::class, $data);
        $invoiceId = (string) ($uriVariables['invoiceId'] ?? '');

        if ('' === $invoiceId) {
            throw new \InvalidArgumentException('Invoice id is required.');
        }

        $task = new InvoiceTransitionTask($invoiceId, $input->transition);

        return $this->handler->handle($task);
    }
}
