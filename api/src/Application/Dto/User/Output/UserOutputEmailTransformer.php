<?php

declare(strict_types=1);

namespace App\Application\Dto\User\Output;

use App\Application\Guard\TypeGuard;
use App\Domain\Entity\User\User;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<User, UserOutput> */
final class UserOutputEmailTransformer implements TransformCallableInterface
{
    public function __invoke(mixed $value, object $source, ?object $target): string
    {
        $user = TypeGuard::assertClass(User::class, $source);

        return $user->contact->email ?? $user->userIdentifier;
    }
}
