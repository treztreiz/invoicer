<?php

namespace App\Infrastructure\Security;

use App\Domain\Entity\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private User $user,
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->user->userIdentifier;
    }

    public function getRoles(): array
    {
        return $this->user->roles;
    }

    public function getPassword(): ?string
    {
        return $this->user->password;
    }

    public function eraseCredentials(): void
    {
        // no temporary credentials stored on the domain entity
    }

    public function getDomainUser(): User
    {
        return $this->user;
    }
}