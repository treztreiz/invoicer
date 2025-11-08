<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class SecurityGuard
{
    public static function assertAuth(?object $user): SecurityUser
    {
        if (!$user instanceof SecurityUser) {
            throw new AuthenticationCredentialsNotFoundException('User is not authenticated.');
        }

        return $user;
    }
}
