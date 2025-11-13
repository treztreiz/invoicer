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
use App\Application\UseCase\Quote\Task\GetQuoteTask;
use App\Application\UseCase\Quote\Task\ListQuotesTask;
use App\Domain\Filter\QuoteFilterCollection;
use App\Infrastructure\ApiPlatform\Guard\ParameterGuard;

/** @implements ProviderInterface<QuoteOutput> */
final readonly class QuoteStateProvider implements ProviderInterface
{
    public function __construct(
        private ListQuotesHandler $listQuotesHandler,
        private GetQuoteHandler $getQuoteHandler,
    ) {
    }

    /** @return QuoteOutput|list<QuoteOutput> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): QuoteOutput|array
    {
        if ($operation instanceof GetCollection) {
            $filters = $this->buildFilters($operation);

            return $this->listQuotesHandler->handle(new ListQuotesTask($filters));
        }

        if ($operation instanceof Get) {
            $quoteId = (string) ($uriVariables['quoteId'] ?? '');

            return $this->getQuoteHandler->handle(new GetQuoteTask($quoteId));
        }

        throw new \LogicException(sprintf('Unsupported operation %s for quote provider.', $operation::class));
    }

    private function buildFilters(GetCollection $operation): QuoteFilterCollection
    {
        $parameters = $operation->getParameters();

        if (null === $parameters) {
            return new QuoteFilterCollection();
        }

        return new QuoteFilterCollection(
            statuses: ParameterGuard::get($parameters, 'status', [])
        );
    }
}
