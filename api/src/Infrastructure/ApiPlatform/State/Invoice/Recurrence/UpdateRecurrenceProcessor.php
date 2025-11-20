<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Invoice\Recurrence;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Dto\Invoice\Input\Recurrence\RecurrenceInput;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Recurrence\UpdateRecurrenceUseCase;

/**
 * @implements ProcessorInterface<RecurrenceInput, InvoiceOutput>
 */
final readonly class UpdateRecurrenceProcessor implements ProcessorInterface
{
    public function __construct(private UpdateRecurrenceUseCase $handler)
    {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): InvoiceOutput
    {
        $input = TypeGuard::assertClass(RecurrenceInput::class, $data);
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
