<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Invoice\Installment;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Dto\Invoice\Input\Installment\InstallmentPlanInput;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Installment\DetachInstallmentPlanUseCase;

/**
 * @implements ProcessorInterface<InstallmentPlanInput, InvoiceOutput>
 */
final readonly class DetachInstallmentPlanProcessor implements ProcessorInterface
{
    public function __construct(private DetachInstallmentPlanUseCase $handler)
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
