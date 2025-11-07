<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Query;

final readonly class GetCustomerQuery
{
    public function __construct(public string $id)
    {
    }
}
