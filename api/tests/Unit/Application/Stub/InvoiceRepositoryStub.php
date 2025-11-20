<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Stub;

use App\Domain\Contracts\Repository\InvoiceRepositoryInterface;
use App\Domain\Entity\Document\Invoice\Invoice;
use Symfony\Component\Uid\Uuid;

final class InvoiceRepositoryStub implements InvoiceRepositoryInterface
{
    public function __construct(private ?Invoice $invoice = null)
    {
    }

    public function save(Invoice $invoice): void
    {
        $this->invoice = $invoice;
    }

    public function remove(Invoice $invoice): void
    {
    }

    public function findOneById(Uuid $id): ?Invoice
    {
        return $this->invoice;
    }

    public function list(): array
    {
        return $this->invoice ? [$this->invoice] : [];
    }
}
