<?php

/** @noinspection PhpParameterNameChangedDuringInheritanceInspection */

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Entity\User\User;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<SecurityUser>
 */
final readonly class SecurityUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class || is_subclass_of($class, SecurityUser::class);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        /** @var User $user */
        $user = SecurityGuard::assertExists(
            $this->userRepository->findOneByUserIdentifier($identifier),
            $identifier
        );

        return new SecurityUser($user);
    }

    public function refreshUser(UserInterface $securityUser): UserInterface
    {
        $securityUser = SecurityGuard::assertSupported($securityUser);

        /** @var User $user */
        $user = SecurityGuard::assertExists(
            $this->userRepository->findOneById($securityUser->user->id),
            $securityUser->getUserIdentifier()
        );

        return new SecurityUser($user);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $securityUser, string $newHashedPassword): void
    {
        $user = SecurityGuard::assertSupported($securityUser)->user;
        $user->updatePassword($newHashedPassword);

        $this->userRepository->save($user);
    }
}
