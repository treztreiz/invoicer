<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Quote\Output\Mapper\QuoteOutputMapper;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Application\UseCase\Quote\Query\ListQuotesQuery;
use App\Domain\Contracts\QuoteRepositoryInterface;
use App\Domain\Entity\Document\Quote;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\WorkflowInterface;

/** @implements UseCaseHandlerInterface<ListQuotesQuery, QuoteOutput> */
final readonly class ListQuotesHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private QuoteRepositoryInterface $quoteRepository,
        private QuoteOutputMapper $outputMapper,
        #[Autowire(service: 'state_machine.quote_flow')]
        private WorkflowInterface $quoteWorkflow,
    ) {
    }

    /**
     * @return list<QuoteOutput>
     */
    public function handle(object $data): array
    {
        TypeGuard::assertClass(ListQuotesQuery::class, $data);

        $quotes = $this->quoteRepository->list();

        return array_map(
            fn ($quote) => $this->outputMapper->map($quote, $this->availableActions($quote)),
            $quotes
        );
    }

    /**
     * @return list<string>
     */
    private function availableActions(Quote $quote): array
    {
        return array_values(
            array_map(
                static fn ($transition) => $transition->getName(),
                $this->quoteWorkflow->getEnabledTransitions($quote)
            )
        );
    }
}
