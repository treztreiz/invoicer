<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\TypeGuard;
use App\Application\Service\EntityFetcher;
use App\Application\UseCase\User\Output\Mapper\UserOutputMapper;
use App\Application\UseCase\User\Output\UserOutput;
use App\Application\UseCase\User\Task\GetUserTask;

/** @implements UseCaseHandlerInterface<GetUserTask,UserOutput> */
final readonly class GetUserHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private EntityFetcher $entityFetcher,
        private UserOutputMapper $mapper,
    ) {
    }

    public function handle(object $data): UserOutput
    {
        $task = TypeGuard::assertClass(GetUserTask::class, $data);

        $user = $this->entityFetcher->user($task->userId);

        return $this->mapper->map($user);
    }
}
