<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Dto\User\Input\UserInput;
use App\Application\Dto\User\Output\UserOutput;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\User\UpdateUserUseCase;
use App\Infrastructure\Security\SecurityGuard;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<UserInput, UserOutput>
 */
final readonly class UpdateUserProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private UpdateUserUseCase $handler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): UserOutput
    {
        $userInput = TypeGuard::assertClass(UserInput::class, $data);
        $securityUser = SecurityGuard::assertAuth($this->security->getUser());

        return $this->handler->handle(
            input: $userInput,
            userId: $securityUser->user->id->toRfc4122()
        );
    }
}
