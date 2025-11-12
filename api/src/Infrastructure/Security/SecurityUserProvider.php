<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Infrastructure\Doctrine\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<SecurityUser>
 */
final readonly class SecurityUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class || is_subclass_of($class, SecurityUser::class);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $domainUser = $this->userRepository->findOneByUserIdentifier($identifier);

        if (null === $domainUser) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return new SecurityUser($domainUser);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $domainUser = $this->userRepository->findOneById($user->id);

        if (null === $domainUser) {
            throw new UserNotFoundException(sprintf('User "%s" no longer exists.', $user->userIdentifier));
        }

        return new SecurityUser($domainUser);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $domainUser = $user->domainUser;
        $domainUser->updatePassword($newHashedPassword);
        $this->userRepository->save($domainUser);
    }
}
