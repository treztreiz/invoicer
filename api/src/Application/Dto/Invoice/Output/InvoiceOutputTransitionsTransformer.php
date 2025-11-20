<?php

declare(strict_types=1);

namespace App\Application\Dto\Invoice\Output;

use App\Application\Guard\TypeGuard;
use App\Application\Service\Trait\DocumentWorkflowManagerAwareTrait;
use App\Domain\Entity\Document\Invoice\Invoice;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<Invoice, InvoiceOutput> */
final class InvoiceOutputTransitionsTransformer implements TransformCallableInterface
{
    use DocumentWorkflowManagerAwareTrait;

    /**
     * @return list<string>
     */
    public function __invoke(mixed $value, object $source, ?object $target): array
    {
        $invoice = TypeGuard::assertClass(Invoice::class, $source);

        return $this->documentWorkflowManager->getInvoiceTransitions($invoice);
    }
}
