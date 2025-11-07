<?php

declare(strict_types=1);

namespace App\Application\UseCase\Me\Handler;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\UseCase\Me\Input\ChangePasswordInput;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entity\User\User;
use App\Infrastructure\Security\SecurityUser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final readonly class ChangePasswordHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function handle(object $input): object
    {
        if (!$input instanceof ChangePasswordInput) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s.', ChangePasswordInput::class, $input::class));
        }

        $userId = Uuid::fromString($input->userId);
        $user = $this->userRepository->findOneById($userId);

        if (!$user instanceof User) {
            throw new \RuntimeException('Authenticated user could not be found.');
        }

        $securityUser = new SecurityUser($user);

        if (!$this->passwordHasher->isPasswordValid($securityUser, $input->currentPassword)) {
            throw new ValidationException(new ConstraintViolationList([new ConstraintViolation(message: 'Current password is invalid.', messageTemplate: 'Current password is invalid.', parameters: [], root: null, propertyPath: 'currentPassword', invalidValue: null)]));
        }

        $user->password = $this->passwordHasher->hashPassword($securityUser, $input->newPassword);
        $this->userRepository->save($user);

        return $input;
    }
}
