<?php

declare(strict_types=1);

namespace App\Application\Dto\Quote\Descriptor;

use App\Application\Dto\Document\Descriptor\DocumentLineDescriptorTransformer;
use App\Application\Dto\Document\Input\DocumentCustomerInputTransformer;
use App\Application\Service\Transformer\OutputTransformer;
use App\Domain\Entity\Document\DocumentLine;
use App\Domain\Entity\Document\Quote\Quote;
use App\Domain\Payload\Invoice\InvoicePayload;
use App\Domain\ValueObject\VatRate;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: InvoicePayload::class, source: Quote::class)]
class ConvertQuoteToInvoiceDescriptor
{
    public function __construct(
        private(set) readonly string $title,

        private(set) ?string $subtitle {
            get => $this->subtitle ?? null;
            set => $value;
        },

        #[Map(source: 'customer.id', transform: [OutputTransformer::class, 'uuid'])]
        #[Map(target: 'customer', source: 'customer.id', transform: DocumentCustomerInputTransformer::class)]
        private(set) readonly string $customerId,

        private(set) readonly string $currency,

        private(set) readonly VatRate $vatRate,

        /** @var ArrayCollection<int, DocumentLine> */
        #[Map(target: 'linesPayload', transform: DocumentLineDescriptorTransformer::class)]
        private(set) readonly Collection $lines,
    ) {
    }
}
