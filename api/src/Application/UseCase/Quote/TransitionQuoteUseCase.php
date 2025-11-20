<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote;

use App\Application\Dto\Quote\Input\TransitionQuoteInput;
use App\Application\Dto\Quote\Output\QuoteOutput;
use App\Application\Service\Trait\DocumentWorkflowManagerAwareTrait;
use App\Application\Service\Trait\QuoteRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\Document\Quote;
use App\Domain\Exception\DocumentTransitionException;

final class TransitionQuoteUseCase extends AbstractUseCase
{
    use DocumentWorkflowManagerAwareTrait;
    use QuoteRepositoryAwareTrait;

    private const string ACTION_SEND = 'send';
    private const string ACTION_ACCEPT = 'accept';
    private const string ACTION_REJECT = 'reject';

    public function handle(TransitionQuoteInput $input, string $quoteId): QuoteOutput
    {
        $quote = $this->findOneById($this->quoteRepository, $quoteId, Quote::class);

        $transition = $input->transition;

        if (!$this->documentWorkflowManager->canQuoteTransition($quote, $transition)) {
            throw new DocumentTransitionException(sprintf('Quote cannot transition via "%s".', $transition));
        }

        $this->applyTransition($quote, $transition);
        $this->quoteRepository->save($quote);

        return $this->objectMapper->map($quote, QuoteOutput::class);
    }

    private function applyTransition(Quote $quote, string $transition): void
    {
        $now = new \DateTimeImmutable();

        match ($transition) {
            self::ACTION_SEND => $quote->send($now),
            self::ACTION_ACCEPT => $quote->markAccepted($now),
            self::ACTION_REJECT => $quote->markRejected($now),
            default => throw new DocumentTransitionException(sprintf('Unknown transition "%s".', $transition)),
        };
    }
}
