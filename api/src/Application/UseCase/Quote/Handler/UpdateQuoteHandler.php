<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\QuoteGuard;
use App\Application\Guard\TypeGuard;
use App\Application\Service\EntityFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Quote\Input\Mapper\QuotePayloadMapper;
use App\Application\UseCase\Quote\Output\Mapper\QuoteOutputMapper;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Application\UseCase\Quote\Task\UpdateQuoteTask;
use App\Domain\Contracts\QuoteRepositoryInterface;

/** @implements UseCaseHandlerInterface<UpdateQuoteTask, QuoteOutput> */
final readonly class UpdateQuoteHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private QuoteRepositoryInterface $quoteRepository,
        private QuotePayloadMapper $payloadMapper,
        private QuoteOutputMapper $outputMapper,
        private EntityFetcher $entityFetcher,
        private DocumentWorkflowManager $workflowManager,
    ) {
    }

    public function handle(object $data): QuoteOutput
    {
        $task = TypeGuard::assertClass(UpdateQuoteTask::class, $data);
        $quote = QuoteGuard::assertDraft(
            $this->entityFetcher->quote($task->quoteId)
        );

        $input = $task->input;
        $customer = $this->entityFetcher->customer($input->customerId);
        $user = $this->entityFetcher->user($input->userId);

        $payload = $this->payloadMapper->map($input, $customer, $user);
        $quote->applyPayload($payload);

        $this->quoteRepository->save($quote);

        return $this->outputMapper->map(
            $quote,
            $this->workflowManager->quoteActions($quote)
        );
    }
}
