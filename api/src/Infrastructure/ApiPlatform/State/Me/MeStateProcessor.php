<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Me;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\UseCase\Me\Command\MeCommand;
use App\Application\UseCase\Me\Handler\UpdateProfileHandler;
use App\Application\UseCase\Me\Mapper\MeCommandMapper;
use App\Application\UseCase\Me\Mapper\MeResultMapper;
use App\Application\UseCase\Me\Result\MeResult;
use App\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

final readonly class MeStateProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private UpdateProfileHandler $handler,
        private MeCommandMapper $commandMapper,
        private MeResultMapper $resultMapper,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): MeResult
    {
        if (!$data instanceof MeCommand) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s.', MeCommand::class, is_object($data) ? $data::class : gettype($data)));
        }

        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser) {
            throw new AuthenticationCredentialsNotFoundException('User is not authenticated.');
        }

        $command = $this->commandMapper->fromPayload($data);
        $command->userId = $user->domainUser->id->toRfc4122();

        $updatedUser = $this->handler->handle($command);

        return $this->resultMapper->toResult($updatedUser);
    }
}
