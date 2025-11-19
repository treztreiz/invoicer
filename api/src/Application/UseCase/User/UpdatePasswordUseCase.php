<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Application\Contract\UserPasswordHasherInterface;
use App\Application\Dto\User\Input\PasswordInput;
use App\Application\Service\Trait\UserRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\User\User;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final class UpdatePasswordUseCase extends AbstractUseCase
{
    use UserRepositoryAwareTrait;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function handle(PasswordInput $input, string $userId): void
    {
        $user = $this->findOneById($this->userRepository, $userId, User::class);

        if (!$this->passwordHasher->isValid($user, $input->currentPassword)) {
            throw new ValidationException(new ConstraintViolationList([new ConstraintViolation(message: 'Current password is invalid.', messageTemplate: 'Current password is invalid.', parameters: [], root: null, propertyPath: 'currentPassword', invalidValue: null)]));
        }

        $password = $this->passwordHasher->hash($user, $input->newPassword);
        $user->updatePassword($password);

        $this->userRepository->save($user);
    }
}
