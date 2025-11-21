<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Application\Dto\User\Input\UserInput;
use App\Application\Dto\User\Output\UserOutput;
use App\Application\Service\Trait\UserRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\User\User;
use App\Domain\Payload\User\UserPayload;

final class UpdateUserUseCase extends AbstractUseCase
{
    use UserRepositoryAwareTrait;

    public function handle(UserInput $input, string $userId): UserOutput
    {
        $user = $this->findOneById($this->userRepository, $userId, User::class);

        $payload = $this->map($input, UserPayload::class);
        $user->applyPayload($payload);

        $this->userRepository->save($user);

        return $this->map($user, UserOutput::class);
    }
}
