<?php

declare(strict_types=1);

namespace App\Application\Dto\Document\Input;

use App\Application\Dto\Invoice\Input\InvoiceInput;
use App\Application\Dto\Quote\Input\QuoteInput;
use App\Application\Service\Document\DocumentLinePayloadFactory;
use App\Application\Service\Trait\ObjectMapperAwareTrait;
use App\Application\Service\Transformer\InputTransformer;
use App\Domain\Payload\Document\DocumentLinePayloadCollection;
use App\Domain\Payload\Document\InvoicePayload;
use App\Domain\Payload\Document\QuotePayload;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<InvoiceInput|QuoteInput, InvoicePayload|QuotePayload> */
class DocumentLineInputTransformer implements TransformCallableInterface
{
    use ObjectMapperAwareTrait;

    public function __construct(private readonly DocumentLinePayloadFactory $linePayloadFactory)
    {
    }

    /**
     * @param list<DocumentLineInput> $value
     */
    public function __invoke(mixed $value, object $source, ?object $target): DocumentLinePayloadCollection
    {
        $vatRate = InputTransformer::vatRate($source->vatRate, $source);

        return $this->linePayloadFactory->build($value, $vatRate->value);
    }
}
