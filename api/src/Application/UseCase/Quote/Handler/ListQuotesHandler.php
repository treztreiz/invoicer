<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\TypeGuard;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Quote\Output\Mapper\QuoteOutputMapper;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Application\UseCase\Quote\Task\ListQuotesTask;
use App\Domain\Contracts\QuoteRepositoryInterface;

/** @implements UseCaseHandlerInterface<ListQuotesTask, QuoteOutput> */
final readonly class ListQuotesHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private QuoteRepositoryInterface $quoteRepository,
        private QuoteOutputMapper $outputMapper,
        private DocumentWorkflowManager $workflowManager,
    ) {
    }

    /**
     * @return list<QuoteOutput>
     */
    public function handle(object $data): array
    {
        TypeGuard::assertClass(ListQuotesTask::class, $data);

        $quotes = $this->quoteRepository->list();

        return array_map(
            fn ($quote) => $this->outputMapper->map(
                $quote,
                $this->workflowManager->quoteActions($quote)
            ),
            $quotes
        );
    }
}
