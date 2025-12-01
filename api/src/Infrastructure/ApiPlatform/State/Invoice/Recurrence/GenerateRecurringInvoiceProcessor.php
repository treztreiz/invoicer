<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Invoice\Recurrence;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Recurrence\GenerateRecurringInvoiceUseCase;
use App\Infrastructure\Security\SecurityGuard;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<null, InvoiceOutput>
 */
final readonly class GenerateRecurringInvoiceProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private GenerateRecurringInvoiceUseCase $useCase,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): InvoiceOutput
    {
        SecurityGuard::assertAuth($this->security->getUser());
        $invoiceId = $uriVariables['invoiceId'] ?? null;

        if (null === $invoiceId) {
            throw new \InvalidArgumentException('Invoice id is required.');
        }

        return $this->useCase->handle($invoiceId, true);
    }
}
