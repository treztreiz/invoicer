<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\TypeGuard;
use App\Application\Service\Document\DocumentFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Quote\Output\Mapper\QuoteOutputMapper;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Application\UseCase\Quote\Task\GetQuoteTask;

/** @implements UseCaseHandlerInterface<GetQuoteTask, QuoteOutput> */
final readonly class GetQuoteHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private DocumentFetcher $documentFetcher,
        private QuoteOutputMapper $outputMapper,
        private DocumentWorkflowManager $workflowManager,
    ) {
    }

    public function handle(object $data): QuoteOutput
    {
        $task = TypeGuard::assertClass(GetQuoteTask::class, $data);

        $quote = $this->documentFetcher->quote($task->quoteId);

        return $this->outputMapper->map(
            $quote,
            $this->workflowManager->quoteActions($quote)
        );
    }
}
