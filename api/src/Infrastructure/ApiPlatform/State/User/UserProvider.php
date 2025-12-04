<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\User\GetUserUseCase;
use App\Domain\Entity\User\User;
use App\Infrastructure\Security\SecurityGuard;
use Symfony\Bundle\SecurityBundle\Security;

/** @implements ProviderInterface<User> */
final readonly class UserProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private GetUserUseCase $handler,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $securityUser = SecurityGuard::assertAuth($this->security->getUser());

        return $this->handler->handle(
            userId: $securityUser->user->id->toRfc4122()
        );
    }
}
