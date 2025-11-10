<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Query;

final readonly class GetInvoiceQuery
{
    public function __construct(public string $id)
    {
    }
}
