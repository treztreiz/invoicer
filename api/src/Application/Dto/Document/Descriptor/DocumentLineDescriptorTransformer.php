<?php

declare(strict_types=1);

namespace App\Application\Dto\Document\Descriptor;

use App\Application\Guard\TypeGuard;
use App\Application\Service\Trait\ObjectMapperAwareTrait;
use App\Domain\Contracts\Payload\DocumentPayloadInterface;
use App\Domain\Entity\Document\Document;
use App\Domain\Entity\Document\DocumentLine;
use App\Domain\Payload\Document\DocumentLinePayload;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<Document, DocumentPayloadInterface> */
class DocumentLineDescriptorTransformer implements TransformCallableInterface
{
    use ObjectMapperAwareTrait;

    /**
     * @param ArrayCollection<int, DocumentLine> $value
     *
     * @return list<DocumentLinePayload>
     */
    public function __invoke(mixed $value, object $source, ?object $target): array
    {
        return TypeGuard::assertClass(Collection::class, $value)
            ->map(fn (DocumentLine $documentLine) => $this->transform($documentLine))
            ->getValues();
    }

    private function transform(DocumentLine $documentLine): DocumentLinePayload
    {
        return $this->objectMapper->map($documentLine, DocumentLinePayload::class);
    }
}
