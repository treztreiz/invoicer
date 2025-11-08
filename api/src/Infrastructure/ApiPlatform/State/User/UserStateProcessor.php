<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\User\Handler\UpdateUserHandler;
use App\Application\UseCase\User\Input\UserInput;
use App\Application\UseCase\User\Output\UserOutput;
use App\Infrastructure\Security\SecurityGuard;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<UserInput, UserOutput>
 */
final readonly class UserStateProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private UpdateUserHandler $handler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): UserOutput
    {
        $userInput = TypeGuard::assertClass(UserInput::class, $data);
        $user = SecurityGuard::assertAuth($this->security->getUser());

        $userInput->id = $user->domainUser->id->toRfc4122();

        return $this->handler->handle($userInput);
    }
}
