<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Service\UserPasswordHasherInterface as ApplicationPasswordHasherInterface;
use App\Domain\Entity\User\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserPasswordHasher implements ApplicationPasswordHasherInterface
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function hash(User $user, string $plainPassword): string
    {
        return $this->passwordHasher->hashPassword(new SecurityUser($user), $plainPassword);
    }

    public function isValid(User $user, string $plainPassword): bool
    {
        return $this->passwordHasher->isPasswordValid(new SecurityUser($user), $plainPassword);
    }
}
