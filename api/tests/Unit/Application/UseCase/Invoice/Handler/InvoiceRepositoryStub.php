<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Invoice\Handler;

use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Entity\Document\Invoice;
use Symfony\Component\Uid\Uuid;

final readonly class InvoiceRepositoryStub implements InvoiceRepositoryInterface
{
    public function __construct(private Invoice $invoice)
    {
    }

    public function save(Invoice $invoice): void
    {
        // no-op for unit tests
    }

    public function remove(Invoice $invoice): void
    {
    }

    public function findOneById(Uuid $id): Invoice
    {
        return $this->invoice;
    }

    public function list(): array
    {
        return [$this->invoice];
    }
}
