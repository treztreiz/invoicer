<?php

namespace App\Infrastructure\Security;

use App\Infrastructure\Persistence\Doctrine\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final readonly class SecurityUserProvider implements UserProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function supportsClass(string $class): bool
    {
        return $class === SecurityUser::class || is_subclass_of($class, SecurityUser::class);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findOneByUserIdentifier($identifier);

        if (null === $user) {
            throw new UserNotFoundException((sprintf('User "%s" not found.', $identifier)));
        }

        return new SecurityUser($user);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $domainUser = $this->userRepository->findOneById($user->getDomainUser()->id);

        if (null === $domainUser) {
            throw new UserNotFoundException(sprintf('User "%s" no longer exists.', $user->getUserIdentifier()));
        }

        return new SecurityUser($domainUser);
    }
}