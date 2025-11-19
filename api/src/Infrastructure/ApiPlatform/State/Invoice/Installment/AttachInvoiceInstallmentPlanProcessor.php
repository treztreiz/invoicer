<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Invoice\Installment;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Dto\Invoice\Input\Installment\InstallmentPlanInput;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Installment\AttachInstallmentPlanUseCase;

/**
 * @implements ProcessorInterface<InstallmentPlanInput, InvoiceOutput>
 */
final readonly class AttachInvoiceInstallmentPlanProcessor implements ProcessorInterface
{
    public function __construct(private AttachInstallmentPlanUseCase $handler)
    {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): InvoiceOutput
    {
        $input = TypeGuard::assertClass(InstallmentPlanInput::class, $data);
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
