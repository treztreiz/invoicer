<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\UserNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\User\Output\Mapper\UserOutputMapper;
use App\Application\UseCase\User\Output\UserOutput;
use App\Application\UseCase\User\Task\GetUserTask;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entity\User\User;
use Symfony\Component\Uid\Uuid;

/** @implements UseCaseHandlerInterface<GetUserTask,UserOutput> */
final readonly class GetUserHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserOutputMapper $mapper,
    ) {
    }

    public function handle(object $data): UserOutput
    {
        $task = TypeGuard::assertClass(GetUserTask::class, $data);

        $id = Uuid::fromString($task->userId);
        $user = $this->userRepository->findOneById($id);

        if (!$user instanceof User) {
            throw new UserNotFoundException($task->userId);
        }

        return $this->mapper->map($user);
    }
}
