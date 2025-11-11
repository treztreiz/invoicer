<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Invoice;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\UseCase\Invoice\Handler\DetachInvoiceInstallmentPlanHandler;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Task\DetachInvoiceInstallmentPlanTask;

/**
 * @implements ProcessorInterface<object, InvoiceOutput>
 */
final readonly class InvoiceInstallmentPlanDeleteStateProcessor implements ProcessorInterface
{
    public function __construct(private DetachInvoiceInstallmentPlanHandler $handler)
    {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): InvoiceOutput
    {
        $invoiceId = (string) ($uriVariables['invoiceId'] ?? '');

        if ('' === $invoiceId) {
            throw new \InvalidArgumentException('Invoice id is required.');
        }

        return $this->handler->handle(new DetachInvoiceInstallmentPlanTask($invoiceId));
    }
}
