<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Quote;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Quote\ConvertQuoteToInvoiceUseCase;
use App\Infrastructure\Security\SecurityGuard;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<null, InvoiceOutput>
 */
final readonly class ConvertQuoteToInvoiceProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private ConvertQuoteToInvoiceUseCase $useCase,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): InvoiceOutput
    {
        $securityUser = SecurityGuard::assertAuth($this->security->getUser());
        $quoteId = $uriVariables['quoteId'] ?? null;

        if (null === $quoteId) {
            throw new \InvalidArgumentException('Quote id is required.');
        }

        return $this->useCase->handle($quoteId, $securityUser->user->id->toRfc4122());
    }
}
