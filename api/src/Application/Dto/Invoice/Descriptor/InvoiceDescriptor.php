<?php

declare(strict_types=1);

namespace App\Application\Dto\Invoice\Descriptor;

use App\Application\Dto\Document\Descriptor\DocumentLineDescriptorTransformer;
use App\Application\Dto\Document\Input\DocumentCustomerInputTransformer;
use App\Application\Service\Transformer\OutputTransformer;
use App\Domain\Entity\Document\DocumentLine;
use App\Domain\Entity\Document\Invoice\Invoice;
use App\Domain\Payload\Invoice\InvoicePayload;
use App\Domain\ValueObject\VatRate;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: InvoicePayload::class, source: Invoice::class)]
final class InvoiceDescriptor
{
    public function __construct(
        public string $title,

        public ?string $subtitle,

        public string $currency,

        public VatRate $vatRate,

        #[Map(source: 'customer.id', transform: [OutputTransformer::class, 'uuid'])]
        #[Map(target: 'customer', source: 'customer.id', transform: DocumentCustomerInputTransformer::class)]
        private(set) readonly string $customerId,

        /** @var ArrayCollection<int, DocumentLine> */
        #[Map(target: 'linesPayload', transform: DocumentLineDescriptorTransformer::class)]
        private(set) readonly Collection $lines,
    ) {
    }
}
