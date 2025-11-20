<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Stub;

use App\Domain\Contracts\Repository\QuoteRepositoryInterface;
use App\Domain\Entity\Document\Quote;
use Symfony\Component\Uid\Uuid;

final class QuoteRepositoryStub implements QuoteRepositoryInterface
{
    public function __construct(private ?Quote $quote = null)
    {
    }

    public function save(Quote $quote): void
    {
        $this->quote = $quote;
    }

    public function remove(Quote $quote): void
    {
    }

    public function findOneById(Uuid $id): ?Quote
    {
        return $this->quote;
    }

    public function list(): array
    {
        return $this->quote ? [$this->quote] : [];
    }
}
