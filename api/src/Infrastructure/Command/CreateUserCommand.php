<?php

declare(strict_types=1);

namespace App\Infrastructure\Command;

use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entity\User\User;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Company;
use App\Domain\ValueObject\CompanyLogo;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\VatRate;
use App\Infrastructure\Security\SecurityUser;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user.',
    help: 'This command allows you to create a user'
)]
final readonly class CreateUserCommand
{
    private const string DEFAULT_USER_IDENTIFIER = 'user@test.com';
    private const string DEFAULT_PASSWORD = '123456';

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Email')] ?string $userIdentifier = null,
        #[Argument('Plain password')] ?string $password = null,
    ): int {
        $io->title('User creation');

        $userIdentifier = $userIdentifier ?? $io->ask('Choose an email', self::DEFAULT_USER_IDENTIFIER);
        $password = $password ?? $io->askHidden('Choose a password');

        if (null === $password || '' === $password) {
            $password = self::DEFAULT_PASSWORD;
        }

        $user = new User(
            name: new Name('Admin', 'User'),
            contact: new Contact($userIdentifier, null),
            company: new Company(
                legalName: 'Demo Company',
                contact: new Contact($userIdentifier, null),
                address: new Address('Main St', null, '00000', 'City', null, 'FR'),
                defaultCurrency: 'EUR',
                defaultHourlyRate: new Money('0'),
                defaultDailyRate: new Money('0'),
                defaultVatRate: new VatRate('0'),
            ),
            companyLogo: CompanyLogo::empty(),
            userIdentifier: $userIdentifier,
            roles: ['ROLE_USER'],
            password: 'test',
            locale: 'fr_FR',
        );

        $password = $this->passwordHasher->hashPassword(new SecurityUser($user), $password);
        $user->updatePassword($password);
        $this->userRepository->save($user);

        $io->success(sprintf('User "%s" created.', $userIdentifier));

        return Command::SUCCESS;
    }
}
