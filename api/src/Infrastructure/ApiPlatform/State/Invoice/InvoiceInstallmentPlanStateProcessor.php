<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Invoice;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Command\AttachInvoiceInstallmentPlanCommand;
use App\Application\UseCase\Invoice\Handler\AttachInvoiceInstallmentPlanHandler;
use App\Application\UseCase\Invoice\Input\InvoiceInstallmentPlanInput;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;

/**
 * @implements ProcessorInterface<InvoiceInstallmentPlanInput, InvoiceOutput>
 */
final readonly class InvoiceInstallmentPlanStateProcessor implements ProcessorInterface
{
    public function __construct(private AttachInvoiceInstallmentPlanHandler $handler)
    {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): InvoiceOutput
    {
        $input = TypeGuard::assertClass(InvoiceInstallmentPlanInput::class, $data);
        $invoiceId = (string) ($uriVariables['id'] ?? '');

        if ('' === $invoiceId) {
            throw new \InvalidArgumentException('Invoice id is required.');
        }

        $replaceExisting = $operation instanceof Put;

        return $this->handler->handle(new AttachInvoiceInstallmentPlanCommand(
            invoiceId: $invoiceId,
            input: $input,
            replaceExisting: $replaceExisting,
        ));
    }
}
