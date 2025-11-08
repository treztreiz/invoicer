<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Query;

final readonly class GetQuoteQuery
{
    public function __construct(public string $id)
    {
    }
}
