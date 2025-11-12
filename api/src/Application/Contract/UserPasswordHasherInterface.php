<?php

declare(strict_types=1);

namespace App\Application\Contract;

use App\Domain\Entity\User\User;

interface UserPasswordHasherInterface
{
    public function hash(User $user, string $plainPassword): string;

    public function isValid(User $user, string $plainPassword): bool;
}
