<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Task;

final class CustomerStatusTask
{
    public function __construct(public string $customerId)
    {
    }
}
