<?php

declare(strict_types=1);

namespace App\Application\UseCase\Me\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\UseCase\Me\Input\CompanyAddressInput;
use App\Application\UseCase\Me\Input\MeInput;
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

    public function handle(object $input): User
    {
        if (!$input instanceof MeInput) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s.', MeInput::class, $input::class));
        }

        $userId = Uuid::fromString($input->userId);
        $user = $this->userRepository->findOneById($userId);

        if (!$user instanceof User) {
            throw new \RuntimeException('Authenticated user could not be found.');
        }

        $name = new Name(
            firstName: $input->firstName,
            lastName: $input->lastName,
        );

        $contact = new Contact(
            email: $input->email,
            phone: $input->phone,
        );

        $companyInput = $input->company;
        $companyContact = new Contact(
            email: $companyInput->email,
            phone: $companyInput->phone,
        );

        $addressInput = $companyInput->address;
        $address = $this->mapAddress($addressInput);

        $company = new Company(
            legalName: $companyInput->legalName,
            contact: $companyContact,
            address: $address,
            defaultCurrency: $companyInput->defaultCurrency,
            defaultHourlyRate: new Money($companyInput->defaultHourlyRate),
            defaultDailyRate: new Money($companyInput->defaultDailyRate),
            defaultVatRate: new VatRate($companyInput->defaultVatRate),
            legalMention: $companyInput->legalMention,
        );

        $user->updateProfile(
            $name,
            $contact,
            $company,
            $input->locale,
            $input->email,
        );

        $this->userRepository->save($user);

        return $user;
    }

    private function mapAddress(CompanyAddressInput $address): Address
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
