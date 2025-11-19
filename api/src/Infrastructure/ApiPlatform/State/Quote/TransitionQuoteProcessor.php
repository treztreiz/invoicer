<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Quote;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Dto\Quote\Input\TransitionQuoteInput;
use App\Application\Dto\Quote\Output\QuoteOutput;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Quote\TransitionQuoteUseCase;

/**
 * @implements ProcessorInterface<TransitionQuoteInput, QuoteOutput>
 */
final readonly class TransitionQuoteProcessor implements ProcessorInterface
{
    public function __construct(
        private TransitionQuoteUseCase $handler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): QuoteOutput
    {
        $input = TypeGuard::assertClass(TransitionQuoteInput::class, $data);
        $quoteId = (string) ($uriVariables['quoteId'] ?? '');

        if ('' === $quoteId) {
            throw new \InvalidArgumentException('Quote id is required.');
        }

        return $this->handler->handle(
            input: $input,
            quoteId: $quoteId
        );
    }
}
