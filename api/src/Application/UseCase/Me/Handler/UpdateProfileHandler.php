<?php

declare(strict_types=1);

namespace App\Application\UseCase\Me\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\UseCase\Me\Command\CompanyAddressCommand;
use App\Application\UseCase\Me\Command\MeCommand;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entity\User\User;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Company;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\VatRate;
use Symfony\Component\Uid\Uuid;

final readonly class UpdateProfileHandler implements UseCaseHandlerInterface
{
    public function __construct(private UserRepositoryInterface $userRepository)
    {
    }

    public function handle(object $command): User
    {
        if (!$command instanceof MeCommand) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s.', MeCommand::class, $command::class));
        }

        $userId = Uuid::fromString($command->userId);
        $user = $this->userRepository->findOneById($userId);

        if (!$user instanceof User) {
            throw new \RuntimeException('Authenticated user could not be found.');
        }

        $name = new Name(
            firstName: $command->firstName,
            lastName: $command->lastName,
        );

        $contact = new Contact(
            email: $command->email,
            phone: $command->phone,
        );

        $companyCommand = $command->company;
        $companyContact = new Contact(
            email: $companyCommand->email,
            phone: $companyCommand->phone,
        );

        $addressCommand = $companyCommand->address;
        $address = $this->mapAddress($addressCommand);

        $company = new Company(
            legalName: $companyCommand->legalName,
            contact: $companyContact,
            address: $address,
            defaultCurrency: $companyCommand->defaultCurrency,
            defaultHourlyRate: new Money($companyCommand->defaultHourlyRate),
            defaultDailyRate: new Money($companyCommand->defaultDailyRate),
            defaultVatRate: new VatRate($companyCommand->defaultVatRate),
            legalMention: $companyCommand->legalMention,
        );

        $user->updateProfile(
            $name,
            $contact,
            $company,
            $command->locale,
            $command->email,
        );

        $this->userRepository->save($user);

        return $user;
    }

    private function mapAddress(CompanyAddressCommand $address): Address
    {
        return new Address(
            streetLine1: $address->streetLine1,
            streetLine2: $address->streetLine2,
            postalCode: $address->postalCode,
            city: $address->city,
            region: $address->region,
            countryCode: $address->countryCode,
        );
    }
}
