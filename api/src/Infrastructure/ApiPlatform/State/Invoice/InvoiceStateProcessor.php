<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Invoice;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Handler\CreateInvoiceHandler;
use App\Application\UseCase\Invoice\Input\InvoiceInput;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Infrastructure\Security\SecurityGuard;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<InvoiceInput, InvoiceOutput>
 */
final readonly class InvoiceStateProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private CreateInvoiceHandler $handler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): InvoiceOutput
    {
        $input = TypeGuard::assertClass(InvoiceInput::class, $data);
        $securityUser = SecurityGuard::assertAuth($this->security->getUser());

        $input->userId = $securityUser->user->id->toRfc4122();

        return $this->handler->handle($input);
    }
}
