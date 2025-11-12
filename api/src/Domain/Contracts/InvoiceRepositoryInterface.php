<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\Entity\Document\Invoice;
use Symfony\Component\Uid\Uuid;

interface InvoiceRepositoryInterface
{
    public function save(Invoice $invoice): void;

    public function remove(Invoice $invoice): void;

    public function findOneById(Uuid $id): ?Invoice;

    /**
     * @return list<Invoice>
     */
    public function list(): array;
}
