<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Quote;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Dto\Quote\Input\QuoteInput;
use App\Application\Dto\Quote\Output\QuoteOutput;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Quote\UpdateQuoteUseCase;
use App\Infrastructure\Security\SecurityGuard;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<QuoteInput, QuoteOutput>
 */
final readonly class UpdateQuoteProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private UpdateQuoteUseCase $handler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): QuoteOutput
    {
        $input = TypeGuard::assertClass(QuoteInput::class, $data);
        $securityUser = SecurityGuard::assertAuth($this->security->getUser());
        $quoteId = (string) ($uriVariables['quoteId'] ?? '');

        if ('' === $quoteId) {
            throw new \InvalidArgumentException('Quote id is required.');
        }

        return $this->handler->handle(
            input: $input,
            quoteId: $quoteId,
            userId: $securityUser->user->id->toRfc4122()
        );
    }
}
