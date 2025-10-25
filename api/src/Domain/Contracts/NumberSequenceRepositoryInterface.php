<?php

namespace App\Domain\Contracts;

use App\Domain\Entity\Numbering\NumberSequence;
use App\Domain\Enum\DocumentType;

interface NumberSequenceRepositoryInterface
{
    public function save(NumberSequence $sequence): void;

    public function findOneByTypeAndYear(DocumentType $documentType, int $year): ?NumberSequence;
}
