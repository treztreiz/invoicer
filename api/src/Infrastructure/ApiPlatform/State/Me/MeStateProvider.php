<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Me;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\Me\Mapper\MeOutputMapper;
use App\Application\UseCase\Me\Output\MeOutput;
use App\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

/** @implements ProviderInterface<MeOutput> */
final readonly class MeStateProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private MeOutputMapper $mapper,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): MeOutput
    {
        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser) {
            throw new AuthenticationCredentialsNotFoundException('User is not authenticated.');
        }

        return $this->mapper->toOutput($user->domainUser);
    }
}
