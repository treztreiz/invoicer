<?php

declare(strict_types=1);

namespace App\Application\Service\Trait;

use App\Domain\Contracts\QuoteRepositoryInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait QuoteRepositoryAwareTrait
{
    protected ?QuoteRepositoryInterface $quoteRepository = null;

    #[Required]
    public function setQuoteRepository(QuoteRepositoryInterface $quoteRepository): void
    {
        $this->quoteRepository = $quoteRepository;
    }
}
