<?php

declare(strict_types=1);

namespace App\Tests\Factory\Document;

use App\Domain\Entity\Document\Invoice;

/** @extends DocumentFactory<Invoice> */
class InvoiceFactory extends DocumentFactory
{
    public static function class(): string
    {
        return Invoice::class;
    }
}
