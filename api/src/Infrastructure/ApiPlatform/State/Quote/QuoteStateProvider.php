<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Quote;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\Quote\Handler\GetQuoteHandler;
use App\Application\UseCase\Quote\Handler\ListQuotesHandler;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Application\UseCase\Quote\Query\GetQuoteQuery;
use App\Application\UseCase\Quote\Query\ListQuotesQuery;

/** @implements ProviderInterface<QuoteOutput|array<int, QuoteOutput>> */
final readonly class QuoteStateProvider implements ProviderInterface
{
    public function __construct(
        private ListQuotesHandler $listQuotesHandler,
        private GetQuoteHandler $getQuoteHandler,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): QuoteOutput|array
    {
        if ($operation instanceof GetCollection) {
            return $this->listQuotesHandler->handle(new ListQuotesQuery());
        }

        if ($operation instanceof Get) {
            $quoteId = (string) ($uriVariables['id'] ?? '');

            return $this->getQuoteHandler->handle(new GetQuoteQuery($quoteId));
        }

        throw new \LogicException(sprintf('Unsupported operation %s for quote provider.', $operation::class));
    }
}
