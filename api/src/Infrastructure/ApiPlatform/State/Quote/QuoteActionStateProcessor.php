<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Quote;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Quote\Handler\QuoteActionHandler;
use App\Application\UseCase\Quote\Input\QuoteActionInput;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Application\UseCase\Quote\Task\QuoteActionTask;

/**
 * @implements ProcessorInterface<QuoteActionInput, QuoteOutput>
 */
final readonly class QuoteActionStateProcessor implements ProcessorInterface
{
    public function __construct(
        private QuoteActionHandler $handler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): QuoteOutput
    {
        $input = TypeGuard::assertClass(QuoteActionInput::class, $data);

        $quoteId = (string) ($uriVariables['id'] ?? '');

        if ('' === $quoteId) {
            throw new \InvalidArgumentException('Quote id is required.');
        }

        $task = new QuoteActionTask($quoteId, $input->action);

        return $this->handler->handle($task);
    }
}
