<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Task;

final readonly class GetCustomerTask
{
    public function __construct(public string $customerId)
    {
    }
}
