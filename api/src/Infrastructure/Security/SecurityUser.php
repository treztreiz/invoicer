<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Entity\User\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(private(set) User $user)
    {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getUserIdentifier(): string
    {
        if ('' === $this->user->userIdentifier) {
            throw new \InvalidArgumentException('User identifier cannot be empty.');
        }

        return $this->user->userIdentifier;
    }

    public function getPassword(): string
    {
        return $this->user->password;
    }

    public function getRoles(): array
    {
        return $this->user->roles;
    }

    public function eraseCredentials(): void
    {
        // no temporary credentials stored on the domain entity
        // Deprecated in symfony 8
    }
}
