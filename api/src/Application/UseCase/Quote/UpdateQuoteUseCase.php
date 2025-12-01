<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote;

use App\Application\Dto\Quote\Input\QuoteInput;
use App\Application\Dto\Quote\Output\QuoteOutput;
use App\Application\Service\Trait\QuoteRepositoryAwareTrait;
use App\Application\Service\Trait\UserRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\Document\Quote\Quote;
use App\Domain\Entity\User\User;
use App\Domain\Payload\Quote\QuotePayload;

final class UpdateQuoteUseCase extends AbstractUseCase
{
    use UserRepositoryAwareTrait;
    use QuoteRepositoryAwareTrait;

    public function handle(QuoteInput $input, string $quoteId, string $userId): QuoteOutput
    {
        $quote = $this->findOneById($this->quoteRepository, $quoteId, Quote::class);
        $user = $this->findOneById($this->userRepository, $userId, User::class);

        $payload = $this->map($input, QuotePayload::class);

        $quote->applyPayload(
            payload: $payload,
            customer: $payload->customer,
            company: $user->company
        );

        $this->quoteRepository->save($quote);

        return $this->map($quote, QuoteOutput::class);
    }
}
