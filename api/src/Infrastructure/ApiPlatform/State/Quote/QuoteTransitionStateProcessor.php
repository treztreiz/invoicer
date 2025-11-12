<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Quote;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Quote\Handler\QuoteTransitionHandler;
use App\Application\UseCase\Quote\Input\QuoteTransitionInput;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Application\UseCase\Quote\Task\QuoteTransitionTask;

/**
 * @implements ProcessorInterface<QuoteTransitionInput, QuoteOutput>
 */
final readonly class QuoteTransitionStateProcessor implements ProcessorInterface
{
    public function __construct(
        private QuoteTransitionHandler $handler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): QuoteOutput
    {
        $input = TypeGuard::assertClass(QuoteTransitionInput::class, $data);

        $quoteId = (string) ($uriVariables['quoteId'] ?? '');

        if ('' === $quoteId) {
            throw new \InvalidArgumentException('Quote id is required.');
        }

        $task = new QuoteTransitionTask($quoteId, $input->transition);

        return $this->handler->handle($task);
    }
}
