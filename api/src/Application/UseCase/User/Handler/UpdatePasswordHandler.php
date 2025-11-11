<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Handler;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\UserNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\User\Input\PasswordInput;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entity\User\User;
use App\Infrastructure\Security\SecurityUser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/** @implements UseCaseHandlerInterface<PasswordInput,null> */
final readonly class UpdatePasswordHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function handle(object $data): null
    {
        $passwordInput = TypeGuard::assertClass(PasswordInput::class, $data);

        $userId = Uuid::fromString($passwordInput->userId);
        $user = $this->userRepository->findOneById($userId);

        if (!$user instanceof User) {
            throw new UserNotFoundException($passwordInput->userId);
        }

        $securityUser = new SecurityUser($user);

        if (!$this->passwordHasher->isPasswordValid($securityUser, $passwordInput->currentPassword)) {
            throw new ValidationException(
                new ConstraintViolationList(
                    [
                        new ConstraintViolation(
                            message: 'Current password is invalid.',
                            messageTemplate: 'Current password is invalid.',
                            parameters: [],
                            root: null,
                            propertyPath: 'currentPassword',
                            invalidValue: null
                        ),
                    ]
                )
            );
        }

        $user->password = $this->passwordHasher->hashPassword($securityUser, $passwordInput->newPassword);
        $this->userRepository->save($user);

        return null;
    }
}
