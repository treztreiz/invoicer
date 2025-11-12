<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\TypeGuard;
use App\Application\Service\EntityFetcher;
use App\Application\UseCase\User\Input\Mapper\UpdateUserMapper;
use App\Application\UseCase\User\Input\UserInput;
use App\Application\UseCase\User\Output\Mapper\UserOutputMapper;
use App\Application\UseCase\User\Output\UserOutput;
use App\Domain\Contracts\UserRepositoryInterface;

/** @implements UseCaseHandlerInterface<UserInput,UserOutput> */
final readonly class UpdateUserHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EntityFetcher $entityFetcher,
        private UpdateUserMapper $mapper,
        private UserOutputMapper $outputMapper,
    ) {
    }

    public function handle(object $data): UserOutput
    {
        $userInput = TypeGuard::assertClass(UserInput::class, $data);

        $user = $this->entityFetcher->user($userInput->userId);

        $payload = $this->mapper->map($userInput, $user->company->logo);
        $user->updateProfile($payload);

        $this->userRepository->save($user);

        return $this->outputMapper->map($user);
    }
}
