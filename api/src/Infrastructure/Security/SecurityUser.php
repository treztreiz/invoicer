<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Entity\User\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

final class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private(set) readonly User $domainUser,
    ) {
    }

    public Uuid $id {
        get => $this->domainUser->id;
    }

    public string $userIdentifier {
        get => $this->domainUser->userIdentifier;
    }

    public string $password {
        get => $this->domainUser->password;
    }

    /** @var array<int, string> */
    public array $roles {
        get => $this->domainUser->roles;
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getUserIdentifier(): string
    {
        if ('' === $this->userIdentifier) {
            throw new \InvalidArgumentException('User identifier cannot be empty.');
        }

        return $this->userIdentifier;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
        // no temporary credentials stored on the domain entity
        // Deprecated in symfony 8
    }
}
