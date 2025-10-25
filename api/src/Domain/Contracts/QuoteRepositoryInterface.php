<?php

namespace App\Domain\Contracts;

use App\Domain\Entity\Document\Quote;
use Symfony\Component\Uid\Uuid;

interface QuoteRepositoryInterface
{
    public function save(Quote $quote): void;

    public function remove(Quote $quote): void;

    public function findOneById(Uuid $id): ?Quote;
}
