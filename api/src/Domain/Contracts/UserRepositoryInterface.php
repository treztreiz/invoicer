<?php

namespace App\Domain\Contracts;

use App\Domain\Entity\User\User;
use Symfony\Component\Uid\Uuid;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function remove(User $user): void;

    public function findOneById(Uuid $id): ?User;

    public function findOneByUserIdentifier(string $userIdentifier): ?User;
}