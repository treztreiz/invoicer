<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Quote\Output\Mapper\QuoteOutputMapper;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Application\UseCase\Quote\Query\ListQuotesQuery;
use App\Domain\Contracts\QuoteRepositoryInterface;

/** @implements UseCaseHandlerInterface<ListQuotesQuery, QuoteOutput> */
final readonly class ListQuotesHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private QuoteRepositoryInterface $quoteRepository,
        private QuoteOutputMapper $outputMapper,
    ) {
    }

    /**
     * @return list<QuoteOutput>
     */
    public function handle(object $data): array
    {
        TypeGuard::assertClass(ListQuotesQuery::class, $data);

        $quotes = $this->quoteRepository->list();

        return array_map(fn ($quote) => $this->outputMapper->map($quote), $quotes);
    }
}
