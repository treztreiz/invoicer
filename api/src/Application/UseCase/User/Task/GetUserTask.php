<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Task;

final readonly class GetUserTask
{
    public function __construct(public string $userId)
    {
    }
}
