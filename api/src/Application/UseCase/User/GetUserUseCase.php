<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Application\Dto\User\Output\UserOutput;
use App\Application\Service\Trait\UserRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\User\User;

final class GetUserUseCase extends AbstractUseCase
{
    use UserRepositoryAwareTrait;

    public function handle(string $userId): UserOutput
    {
        $user = $this->findOneById($this->userRepository, $userId, User::class);

        return $this->objectMapper->map($user, UserOutput::class);
    }
}
