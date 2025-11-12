<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Stub;

use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entity\User\User;
use Symfony\Component\Uid\Uuid;

final class UserRepositoryStub implements UserRepositoryInterface
{
    public function __construct(private ?User $user = null)
    {
    }

    public function save(User $user): void
    {
        $this->user = $user;
    }

    public function remove(User $user): void
    {
    }

    public function findOneById(Uuid $id): ?User
    {
        return $this->user;
    }

    public function findOneByUserIdentifier(string $userIdentifier): ?User
    {
        return $this->user;
    }
}
