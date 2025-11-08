<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\User\Handler\UpdatePasswordHandler;
use App\Application\UseCase\User\Input\PasswordInput;
use App\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

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

        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser) {
            throw new AuthenticationCredentialsNotFoundException('User is not authenticated.');
        }

        $passwordInput->id = $user->domainUser->id->toRfc4122();
        $this->handler->handle($passwordInput);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
