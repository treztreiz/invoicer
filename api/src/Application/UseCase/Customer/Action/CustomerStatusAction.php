<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Action;

final class CustomerStatusAction
{
    public function __construct(public string $id)
    {
    }
}
