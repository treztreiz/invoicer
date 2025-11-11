<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\DomainRuleViolationException;
use App\Application\Guard\TypeGuard;
use App\Application\Service\EntityFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Quote\Output\Mapper\QuoteOutputMapper;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Application\UseCase\Quote\Task\QuoteActionTask;
use App\Domain\Contracts\QuoteRepositoryInterface;
use App\Domain\Entity\Document\Quote;

/** @implements UseCaseHandlerInterface<QuoteActionTask, QuoteOutput> */
final readonly class QuoteActionHandler implements UseCaseHandlerInterface
{
    private const string ACTION_SEND = 'send';
    private const string ACTION_ACCEPT = 'accept';
    private const string ACTION_REJECT = 'reject';

    public function __construct(
        private QuoteRepositoryInterface $quoteRepository,
        private EntityFetcher $entityFetcher,
        private QuoteOutputMapper $outputMapper,
        private DocumentWorkflowManager $workflowManager,
    ) {
    }

    public function handle(object $data): QuoteOutput
    {
        $task = TypeGuard::assertClass(QuoteActionTask::class, $data);

        $quote = $this->entityFetcher->quote($task->quoteId);

        $transition = $task->action;

        if (!$this->workflowManager->canQuoteTransition($quote, $transition)) {
            throw new DomainRuleViolationException(sprintf('Quote cannot transition via "%s".', $transition));
        }

        $this->applyTransition($quote, $transition);
        $this->quoteRepository->save($quote);

        return $this->outputMapper->map(
            $quote,
            $this->workflowManager->quoteActions($quote)
        );
    }

    private function applyTransition(Quote $quote, string $transition): void
    {
        $now = new \DateTimeImmutable();

        match ($transition) {
            self::ACTION_SEND => $quote->send($now),
            self::ACTION_ACCEPT => $quote->markAccepted($now),
            self::ACTION_REJECT => $quote->markRejected($now),
            default => throw new DomainRuleViolationException(sprintf('Unknown action "%s".', $transition)),
        };
    }
}
