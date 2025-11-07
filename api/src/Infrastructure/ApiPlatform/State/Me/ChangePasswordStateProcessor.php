<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Me;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\UseCase\Me\Handler\ChangePasswordHandler;
use App\Application\UseCase\Me\Input\ChangePasswordInput;
use App\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

/**
 * @implements ProcessorInterface<ChangePasswordInput, Response>
 */
final readonly class ChangePasswordStateProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private ChangePasswordHandler $handler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): Response
    {
        /* @phpstan-ignore-next-line defensive runtime guard */
        if (!$data instanceof ChangePasswordInput) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s.', ChangePasswordInput::class, get_debug_type($data)));
        }

        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser) {
            throw new AuthenticationCredentialsNotFoundException('User is not authenticated.');
        }

        $data->userId = $user->domainUser->id->toRfc4122();
        $this->handler->handle($data);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
