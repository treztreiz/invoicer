<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\User\Handler\GetUserHandler;
use App\Application\UseCase\User\Output\UserOutput;
use App\Application\UseCase\User\Query\GetUserQuery;
use App\Infrastructure\Security\SecurityGuard;
use Symfony\Bundle\SecurityBundle\Security;

/** @implements ProviderInterface<UserOutput> */
final readonly class UserStateProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private GetUserHandler $handler,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): UserOutput
    {
        $user = SecurityGuard::assertAuth($this->security->getUser());

        $query = new GetUserQuery($user->domainUser->id->toRfc4122());

        $output = $this->handler->handle($query);

        return $output;
    }
}
