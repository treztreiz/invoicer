<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\UserNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\User\Input\Mapper\UpdateUserMapper;
use App\Application\UseCase\User\Input\UserInput;
use App\Application\UseCase\User\Output\Mapper\UserOutputMapper;
use App\Application\UseCase\User\Output\UserOutput;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entity\User\User;
use Symfony\Component\Uid\Uuid;

/** @implements UseCaseHandlerInterface<UserInput,UserOutput> */
final readonly class UpdateUserHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UpdateUserMapper $mapper,
        private UserOutputMapper $outputMapper,
    ) {
    }

    public function handle(object $data): UserOutput
    {
        $userInput = TypeGuard::assertClass(UserInput::class, $data);

        $userId = Uuid::fromString($userInput->userId);
        $user = $this->userRepository->findOneById($userId);

        if (!$user instanceof User) {
            throw new UserNotFoundException($userInput->userId);
        }

        $payload = $this->mapper->map($userInput);
        $user->updateProfile($payload);

        $this->userRepository->save($user);

        return $this->outputMapper->map($user);
    }
}
