<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\User\Handler\UpdatePasswordHandler;
use App\Application\UseCase\User\Input\PasswordInput;
use App\Infrastructure\Security\SecurityGuard;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<PasswordInput, Response>
 */
final readonly class PasswordStateProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private UpdatePasswordHandler $handler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): Response
    {
        $passwordInput = TypeGuard::assertClass(PasswordInput::class, $data);
        $securityUser = SecurityGuard::assertAuth($this->security->getUser());

        $passwordInput->userId = $securityUser->user->id->toRfc4122();
        $this->handler->handle($passwordInput);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
