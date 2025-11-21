<?php

declare(strict_types=1);

namespace App\Application\Dto\Document\Output;

use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\Dto\Quote\Output\QuoteOutput;
use App\Application\Guard\TypeGuard;
use App\Application\Service\Trait\ObjectMapperAwareTrait;
use App\Domain\Entity\Document\DocumentLine;
use App\Domain\Entity\Document\Invoice\Invoice;
use App\Domain\Entity\Document\Quote\Quote;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<Invoice|Quote, InvoiceOutput|QuoteOutput> */
final class DocumentLineOutputTransformer implements TransformCallableInterface
{
    use ObjectMapperAwareTrait;

    /**
     * @param ArrayCollection<int, DocumentLine> $value
     *
     * @return list<DocumentLineOutput>
     */
    public function __invoke(mixed $value, object $source, ?object $target): array
    {
        return TypeGuard::assertClass(Collection::class, $value)
            ->map(fn (DocumentLine $documentLine) => $this->transform($documentLine))
            ->getValues();
    }

    private function transform(DocumentLine $documentLine): DocumentLineOutput
    {
        return $this->objectMapper->map($documentLine, DocumentLineOutput::class);
    }
}
