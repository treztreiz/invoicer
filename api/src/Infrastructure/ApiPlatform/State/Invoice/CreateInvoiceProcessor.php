<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Invoice;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Dto\Invoice\Input\InvoiceInput;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\CreateInvoiceUseCase;
use App\Infrastructure\Security\SecurityGuard;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<InvoiceInput, InvoiceOutput>
 */
final readonly class CreateInvoiceProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private CreateInvoiceUseCase $handler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): InvoiceOutput
    {
        $input = TypeGuard::assertClass(InvoiceInput::class, $data);
        $securityUser = SecurityGuard::assertAuth($this->security->getUser());

        return $this->handler->handle(
            input: $input,
            userId: $securityUser->user->id->toRfc4122()
        );
    }
}
