<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Invoice;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Command\UpdateInvoiceCommand;
use App\Application\UseCase\Invoice\Handler\UpdateInvoiceHandler;
use App\Application\UseCase\Invoice\Input\InvoiceInput;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Infrastructure\Security\SecurityGuard;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<InvoiceInput, InvoiceOutput>
 */
final readonly class InvoiceUpdateStateProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private UpdateInvoiceHandler $handler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): InvoiceOutput
    {
        $input = TypeGuard::assertClass(InvoiceInput::class, $data);
        $user = SecurityGuard::assertAuth($this->security->getUser());
        $invoiceId = (string) ($uriVariables['id'] ?? '');

        if ('' === $invoiceId) {
            throw new \InvalidArgumentException('Invoice id is required.');
        }

        $input->userId = $user->domainUser->id->toRfc4122();

        return $this->handler->handle(new UpdateInvoiceCommand($invoiceId, $input));
    }
}
