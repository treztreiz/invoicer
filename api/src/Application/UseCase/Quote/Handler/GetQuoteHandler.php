<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Quote\Output\Mapper\QuoteOutputMapper;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Application\UseCase\Quote\Query\GetQuoteQuery;
use App\Application\Workflow\WorkflowActionsHelper;
use App\Domain\Contracts\QuoteRepositoryInterface;
use App\Domain\Entity\Document\Quote;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

/** @implements UseCaseHandlerInterface<GetQuoteQuery, QuoteOutput> */
final readonly class GetQuoteHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private QuoteRepositoryInterface $quoteRepository,
        private QuoteOutputMapper $outputMapper,
        #[Autowire(service: 'state_machine.quote_flow')]
        private WorkflowInterface $quoteWorkflow,
        private WorkflowActionsHelper $actionsHelper,
    ) {
    }

    public function handle(object $data): QuoteOutput
    {
        $query = TypeGuard::assertClass(GetQuoteQuery::class, $data);

        $quote = $this->quoteRepository->findOneById(Uuid::fromString($query->id));

        if (!$quote instanceof Quote) {
            throw new ResourceNotFoundException('Quote', $query->id);
        }

        return $this->outputMapper->map(
            $quote,
            $this->actionsHelper->availableActions($quote, $this->quoteWorkflow)
        );
    }
}
