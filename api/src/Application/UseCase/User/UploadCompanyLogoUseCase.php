<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Application\Contract\CompanyLogoStorageInterface;
use App\Application\Dto\User\Input\UploadCompanyLogoInput;
use App\Application\Dto\User\Output\UserOutput;
use App\Application\Service\Trait\UserRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\User\User;

final class UploadCompanyLogoUseCase extends AbstractUseCase
{
    use UserRepositoryAwareTrait;

    public function __construct(
        private readonly CompanyLogoStorageInterface $logoStorage,
    ) {
    }

    public function handle(UploadCompanyLogoInput $input, string $userId): UserOutput
    {
        $user = $this->findOneById($this->userRepository, $userId, User::class);

        $previousLogo = $user->companyLogo;
        $companyLogo = $this->logoStorage->prepare($input->logo);
        $user->updateCompanyLogo($companyLogo);

        $this->userRepository->save($user);
        $this->logoStorage->commit($input->logo, $companyLogo, $previousLogo);

        return $this->map($user, UserOutput::class);
    }
}
