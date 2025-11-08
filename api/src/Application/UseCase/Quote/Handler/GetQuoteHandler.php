<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Quote\Output\Mapper\QuoteOutputMapper;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Application\UseCase\Quote\Query\GetQuoteQuery;
use App\Domain\Contracts\QuoteRepositoryInterface;
use App\Domain\Entity\Document\Quote;
use Symfony\Component\Uid\Uuid;

/** @implements UseCaseHandlerInterface<GetQuoteQuery, QuoteOutput> */
final readonly class GetQuoteHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private QuoteRepositoryInterface $quoteRepository,
        private QuoteOutputMapper $outputMapper,
    ) {
    }

    public function handle(object $data): QuoteOutput
    {
        $query = TypeGuard::assertClass(GetQuoteQuery::class, $data);

        $quote = $this->quoteRepository->findOneById(Uuid::fromString($query->id));

        if (!$quote instanceof Quote) {
            throw new ResourceNotFoundException('Quote', $query->id);
        }

        return $this->outputMapper->map($quote);
    }
}
