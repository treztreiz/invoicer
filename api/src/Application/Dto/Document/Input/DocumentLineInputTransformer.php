<?php

declare(strict_types=1);

namespace App\Application\Dto\Document\Input;

use App\Application\Dto\Invoice\Input\InvoiceInput;
use App\Application\Dto\Quote\Input\QuoteInput;
use App\Application\Service\Trait\ObjectMapperAwareTrait;
use App\Domain\Payload\Document\DocumentLinePayload;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<InvoiceInput|QuoteInput, \App\Domain\Payload\Invoice\InvoicePayload|\App\Domain\Payload\Quote\QuotePayload> */
final class DocumentLineInputTransformer implements TransformCallableInterface
{
    use ObjectMapperAwareTrait;

    /**
     * @param list<DocumentLineInput> $value
     *
     * @return list<DocumentLinePayload>
     */
    public function __invoke(mixed $value, object $source, ?object $target): array
    {
        return array_map(
            fn (DocumentLineInput $documentLineInput) => $this->transform($documentLineInput),
            $value
        );
    }

    private function transform(DocumentLineInput $documentLineInput): DocumentLinePayload
    {
        return $this->objectMapper->map($documentLineInput, DocumentLinePayload::class);
    }
}
