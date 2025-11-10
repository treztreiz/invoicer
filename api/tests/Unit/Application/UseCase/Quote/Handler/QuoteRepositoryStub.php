<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Quote\Handler;

use App\Domain\Contracts\QuoteRepositoryInterface;
use App\Domain\Entity\Document\Quote;
use Symfony\Component\Uid\Uuid;

final class QuoteRepositoryStub implements QuoteRepositoryInterface
{
    public function __construct(private Quote $quote)
    {
    }

    public function save(Quote $quote): void
    {
        // no-op
    }

    public function remove(Quote $quote): void
    {
    }

    public function findOneById(Uuid $id): Quote
    {
        return $this->quote;
    }

    public function list(): array
    {
        return [$this->quote];
    }
}
