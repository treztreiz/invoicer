<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repository;

use App\Domain\Entity\Document\Quote;
use Symfony\Component\Uid\Uuid;

interface QuoteRepositoryInterface
{
    public function save(Quote $quote): void;

    public function remove(Quote $quote): void;

    public function findOneById(Uuid $id): ?Quote;

    /**
     * @return list<Quote>
     */
    public function list(): array;
}
