<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Invoice;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\Invoice\Handler\GetInvoiceHandler;
use App\Application\UseCase\Invoice\Handler\ListInvoicesHandler;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Task\GetInvoiceTask;
use App\Application\UseCase\Invoice\Task\ListInvoicesTask;

/** @implements ProviderInterface<InvoiceOutput> */
final readonly class InvoiceStateProvider implements ProviderInterface
{
    public function __construct(
        private ListInvoicesHandler $listInvoicesHandler,
        private GetInvoiceHandler $getInvoiceHandler,
    ) {
    }

    /** @return InvoiceOutput|list<InvoiceOutput> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): InvoiceOutput|array
    {
        if ($operation instanceof GetCollection) {
            return $this->listInvoicesHandler->handle(new ListInvoicesTask());
        }

        if ($operation instanceof Get) {
            $invoiceId = (string) ($uriVariables['id'] ?? '');

            return $this->getInvoiceHandler->handle(new GetInvoiceTask($invoiceId));
        }

        throw new \LogicException(sprintf('Unsupported operation %s for invoice provider.', $operation::class));
    }
}
