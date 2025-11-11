<?php

declare(strict_types=1);

namespace App\Application\Service\Document;

use App\Application\Exception\ResourceNotFoundException;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Contracts\QuoteRepositoryInterface;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Entity\Document\Quote;
use Symfony\Component\Uid\Uuid;

final readonly class DocumentFetcher
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private QuoteRepositoryInterface $quoteRepository,
    ) {
    }

    public function invoice(string $id): Invoice
    {
        $invoice = $this->invoiceRepository->findOneById(Uuid::fromString($id));

        if (!$invoice instanceof Invoice) {
            throw new ResourceNotFoundException('Invoice', $id);
        }

        return $invoice;
    }

    public function quote(string $id): Quote
    {
        $quote = $this->quoteRepository->findOneById(Uuid::fromString($id));

        if (!$quote instanceof Quote) {
            throw new ResourceNotFoundException('Quote', $id);
        }

        return $quote;
    }
}
