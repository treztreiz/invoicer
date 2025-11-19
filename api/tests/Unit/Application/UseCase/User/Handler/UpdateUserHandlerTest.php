<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\User\Handler;

use App\Application\Dto\User\Input\CompanyAddressInput;
use App\Application\Dto\User\Input\CompanyInput;
use App\Application\Dto\User\Input\Mapper\UpdateUserMapper;
use App\Application\Dto\User\Input\UserInput;
use App\Application\Dto\User\Output\Mapper\UserOutputMapper;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\UseCase\User\UpdateUserUseCase;
use App\Domain\Entity\User\User;
use App\Tests\Factory\User\UserFactory;
use App\Tests\Unit\Application\Stub\EntityFetcherStub;
use App\Tests\Unit\Application\Stub\UserRepositoryStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @testType sociable-unit
 */
final class UpdateUserHandlerTest extends TestCase
{
    use Factories;

    private UserInput $input;

    protected function setUp(): void
    {
        $this->input = new UserInput(
            firstName: 'Alice',
            lastName: 'Updated',
            email: 'alice@example.com',
            locale: 'fr_FR',
            company: new CompanyInput(
                legalName: 'Acme',
                address: new CompanyAddressInput(
                    streetLine1: '1 rue Test',
                    postalCode: '75000',
                    city: 'Paris',
                    countryCode: 'FR',
                    streetLine2: '2 rue Test',
                ),
                defaultCurrency: 'EUR',
                defaultHourlyRate: '100',
                defaultDailyRate: '800',
                defaultVatRate: '20',
                email: 'contact@acme.test',
                phone: '+33987654321',
            ),
            phone: '+33123456789',
        );

        $this->input->userId = Uuid::v7()->toRfc4122();
    }

    public function test_handle_updates_user_profile(): void
    {
        $user = UserFactory::build()->withId()->create();

        $output = $this->createHandler($user)->handle($this->input);

        static::assertSame('Alice', $output->firstName);
        static::assertSame('alice@example.com', $output->email);
    }

    public function test_handle_throws_when_user_missing(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $this->createHandler()->handle($this->input);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function createHandler(?User $user = null): UpdateUserUseCase
    {
        $repository = new UserRepositoryStub($user);

        return new UpdateUserUseCase(
            userRepository: $repository,
            entityFetcher: EntityFetcherStub::create(
                userRepository: $repository,
            ),
            mapper: new UpdateUserMapper(),
            outputMapper: new UserOutputMapper(),
        );
    }
}
