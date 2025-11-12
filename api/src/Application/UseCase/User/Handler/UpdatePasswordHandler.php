<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Handler;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\TypeGuard;
use App\Application\Service\EntityFetcher;
use App\Application\Service\UserPasswordHasherInterface;
use App\Application\UseCase\User\Input\PasswordInput;
use App\Domain\Contracts\UserRepositoryInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/** @implements UseCaseHandlerInterface<PasswordInput,null> */
final readonly class UpdatePasswordHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EntityFetcher $entityFetcher,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function handle(object $data): null
    {
        $passwordInput = TypeGuard::assertClass(PasswordInput::class, $data);

        $user = $this->entityFetcher->user($passwordInput->userId);

        if (!$this->passwordHasher->isValid($user, $passwordInput->currentPassword)) {
            throw new ValidationException(new ConstraintViolationList([new ConstraintViolation(message: 'Current password is invalid.', messageTemplate: 'Current password is invalid.', parameters: [], root: null, propertyPath: 'currentPassword', invalidValue: null)]));
        }

        $user->password = $this->passwordHasher->hash($user, $passwordInput->newPassword);
        $this->userRepository->save($user);

        return null;
    }
}
