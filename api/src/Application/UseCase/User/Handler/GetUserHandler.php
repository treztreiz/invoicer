<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\UserNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\User\Output\Mapper\UserOutputMapper;
use App\Application\UseCase\User\Output\UserOutput;
use App\Application\UseCase\User\Query\GetUserQuery;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entity\User\User;
use Symfony\Component\Uid\Uuid;

/** @implements UseCaseHandlerInterface<GetUserQuery,UserOutput> */
final readonly class GetUserHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserOutputMapper $mapper,
    ) {
    }

    public function handle(object $data): UserOutput
    {
        $query = TypeGuard::assertClass(GetUserQuery::class, $data);

        $id = Uuid::fromString($query->id);
        $user = $this->userRepository->findOneById($id);

        if (!$user instanceof User) {
            throw new UserNotFoundException($query->id);
        }

        return $this->mapper->map($user);
    }
}
