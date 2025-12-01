<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote;

use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\Dto\Quote\Descriptor\ConvertQuoteToInvoiceDescriptor;
use App\Application\Service\Trait\InvoiceRepositoryAwareTrait;
use App\Application\Service\Trait\QuoteRepositoryAwareTrait;
use App\Application\Service\Trait\UserRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\Document\Invoice\Invoice;
use App\Domain\Entity\Document\Quote\Quote;
use App\Domain\Entity\User\User;
use App\Domain\Enum\QuoteStatus;
use App\Domain\Exception\DocumentRuleViolationException;
use App\Domain\Payload\Invoice\InvoicePayload;

final class ConvertQuoteToInvoiceUseCase extends AbstractUseCase
{
    use QuoteRepositoryAwareTrait;
    use InvoiceRepositoryAwareTrait;
    use UserRepositoryAwareTrait;

    public function handle(string $quoteId, string $userId): InvoiceOutput
    {
        $quote = $this->findOneById($this->quoteRepository, $quoteId, Quote::class);
        $user = $this->findOneById($this->userRepository, $userId, User::class);

        if (QuoteStatus::ACCEPTED !== $quote->status) {
            throw new DocumentRuleViolationException('Only accepted quotes can be converted to an invoice.');
        } elseif ($quote->convertedInvoiceId) {
            throw new DocumentRuleViolationException('Quote has already been converted.');
        }

        $descriptor = $this->map($quote, ConvertQuoteToInvoiceDescriptor::class);
        $payload = $this->map($descriptor, InvoicePayload::class);

        $invoice = Invoice::fromPayload(
            payload: $payload,
            customer: $payload->customer,
            company: $user->company,
        );

        $this->invoiceRepository->save($invoice);
        $quote->linkConvertedInvoice($invoice->id);
        $this->quoteRepository->save($quote);

        return $this->map($invoice, InvoiceOutput::class);
    }
}
