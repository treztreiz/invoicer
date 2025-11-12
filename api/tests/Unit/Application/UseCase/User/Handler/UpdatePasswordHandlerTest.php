<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\User\Handler;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Service\UserPasswordHasherInterface;
use App\Application\UseCase\User\Handler\UpdatePasswordHandler;
use App\Application\UseCase\User\Input\PasswordInput;
use App\Domain\Entity\User\User;
use App\Tests\Factory\User\UserFactory;
use App\Tests\Unit\Application\UseCase\Stub\EntityFetcherStub;
use App\Tests\Unit\Application\UseCase\Stub\UserRepositoryStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @testType sociable-unit
 */
final class UpdatePasswordHandlerTest extends TestCase
{
    use Factories;

    private PasswordInput $input;

    protected function setUp(): void
    {
        $this->input = new PasswordInput(
            currentPassword: 'old',
            newPassword: 'new',
            confirmPassword: 'new',
        );

        $this->input->userId = Uuid::v7()->toRfc4122();
    }

    public function test_handle_updates_password_when_current_valid(): void
    {
        $user = UserFactory::build()->create();

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isValid')->with($user, 'old')->willReturn(true);
        $hasher->method('hash')->with($user, 'new')->willReturn('hashed');

        $this->createHandler($user, $hasher)->handle($this->input);

        static::assertSame('hashed', $user->password);
    }

    public function test_handle_throws_when_current_password_invalid(): void
    {
        $user = UserFactory::build()->create();

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isValid')->willReturn(false);

        $this->expectException(ValidationException::class);

        $this->createHandler($user, $hasher)->handle($this->input);
    }

    public function test_handle_throws_when_user_missing(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $this->createHandler()->handle($this->input);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function createHandler(?User $user = null, ?UserPasswordHasherInterface $hasher = null): UpdatePasswordHandler
    {
        $repository = new UserRepositoryStub($user);

        return new UpdatePasswordHandler(
            userRepository: $repository,
            entityFetcher: EntityFetcherStub::create(userRepository: $repository),
            passwordHasher: $hasher ?: static::createMock(UserPasswordHasherInterface::class),
        );
    }
}
