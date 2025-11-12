<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SecurityGuard
{
    public static function assertAuth(?object $securityUser): SecurityUser
    {
        if (!$securityUser instanceof SecurityUser) {
            throw new AuthenticationCredentialsNotFoundException('User is not authenticated.');
        }

        return $securityUser;
    }

    public static function assertSupported(UserInterface|PasswordAuthenticatedUserInterface $securityUser): SecurityUser
    {
        if (!$securityUser instanceof SecurityUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $securityUser::class));
        }

        return $securityUser;
    }

    /**
     * @template T of object
     *
     * @param T|null $user
     *
     * @return T
     */
    public static function assertExists(?object $user, string $userIdentifier): object
    {
        if (null === $user) {
            throw new UserNotFoundException(sprintf('User "%s" no longer exists.', $userIdentifier));
        }

        return $user;
    }
}
