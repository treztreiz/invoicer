<?php

declare(strict_types=1);

namespace App\Tests\Factory\Document;

use App\Domain\Entity\Document\Invoice;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/** @extends PersistentObjectFactory<Invoice> */
class InvoiceFactory extends DocumentFactory
{
    public static function class(): string
    {
        return Invoice::class;
    }
}
