<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Invoice;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Dto\Invoice\Input\TransitionInvoiceInput;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\TransitionInvoiceUseCase;

/**
 * @implements ProcessorInterface<TransitionInvoiceInput, InvoiceOutput>
 */
final readonly class TransitionInvoiceProcessor implements ProcessorInterface
{
    public function __construct(private TransitionInvoiceUseCase $handler)
    {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): InvoiceOutput
    {
        $input = TypeGuard::assertClass(TransitionInvoiceInput::class, $data);
        $invoiceId = (string) ($uriVariables['invoiceId'] ?? '');

        if ('' === $invoiceId) {
            throw new \InvalidArgumentException('Invoice id is required.');
        }

        return $this->handler->handle(
            input: $input,
            invoiceId: $invoiceId,
        );
    }
}
