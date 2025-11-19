<?php

declare(strict_types=1);

namespace App\Application\Dto\Quote\Output;

use App\Application\Guard\TypeGuard;
use App\Application\Service\Trait\DocumentWorkflowManagerAwareTrait;
use App\Domain\Entity\Document\Quote;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<Quote, QuoteOutput> */
final class QuoteOutputTransitionsTransformer implements TransformCallableInterface
{
    use DocumentWorkflowManagerAwareTrait;

    /**
     * @return list<string>
     */
    public function __invoke(mixed $value, object $source, ?object $target): array
    {
        $quote = TypeGuard::assertClass(Quote::class, $source);

        return $this->documentWorkflowManager->getQuoteTransitions($quote);
    }
}
