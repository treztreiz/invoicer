<?php

declare(strict_types=1);

namespace App\Application\Service\Trait;

use App\Domain\Contracts\InvoiceRepositoryInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait InvoiceRepositoryAwareTrait
{
    protected ?InvoiceRepositoryInterface $invoiceRepository = null;

    #[Required]
    public function setInvoiceRepository(InvoiceRepositoryInterface $invoiceRepository): void
    {
        $this->invoiceRepository = $invoiceRepository;
    }
}
