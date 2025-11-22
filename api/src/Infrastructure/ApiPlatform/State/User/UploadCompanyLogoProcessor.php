<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Dto\User\Input\UploadCompanyLogoInput;
use App\Application\Dto\User\Output\UserOutput;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\User\UploadCompanyLogoUseCase;
use App\Infrastructure\Security\SecurityGuard;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<UploadCompanyLogoInput, UserOutput>
 */
final readonly class UploadCompanyLogoProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private UploadCompanyLogoUseCase $useCase,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): UserOutput
    {
        $input = TypeGuard::assertClass(UploadCompanyLogoInput::class, $data);
        $securityUser = SecurityGuard::assertAuth($this->security->getUser());

        return $this->useCase->handle($input, $securityUser->user->id->toRfc4122());
    }
}
