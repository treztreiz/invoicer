<?php

declare(strict_types=1);

namespace App\Domain\Entity\User;

use App\Domain\DTO\UserUpdateProfilePayload;
use App\Domain\Entity\Common\TimestampableTrait;
use App\Domain\Entity\Common\UuidTrait;
use App\Domain\Guard\DomainGuard;
use App\Domain\ValueObject\Company;
use App\Domain\ValueObject\CompanyLogo;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Name;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'app_user')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USER', fields: ['userIdentifier'])]
class User
{
    use UuidTrait;
    use TimestampableTrait;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private(set) ?\DateTimeImmutable $lastLogin = null;

    public function __construct(
        #[ORM\Embedded(columnPrefix: false)]
        public Name $name,

        #[ORM\Embedded(columnPrefix: false)]
        public Contact $contact,

        #[ORM\Embedded]
        public Company $company,

        #[ORM\Embedded]
        public CompanyLogo $logo,

        #[ORM\Column(length: 180, unique: true)]
        public string $userIdentifier {
            set {
                $value = DomainGuard::nonEmpty($value, 'User identifier');
                $this->userIdentifier = DomainGuard::email($value, 'User identifier');
            }
        },

        /** @var array<int, string> */
        #[ORM\Column]
        public array $roles {
            get => array_unique([...$this->roles, 'ROLE_USER']);
            set => array_unique($value);
        },

        #[ORM\Column]
        public string $password {
            set => DomainGuard::nonEmpty($value, 'Password');
        },

        #[ORM\Column(length: 10)]
        public string $locale {
            set => DomainGuard::nonEmpty($value, 'Locale');
        },
    ) {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function updateProfile(UserUpdateProfilePayload $payload): void
    {
        $this->name = $payload->name;
        $this->contact = $payload->contact;
        $this->company = $payload->company;
        $this->locale = $payload->locale;
        $this->userIdentifier = $payload->userIdentifier;
    }

    public function updateLogo(CompanyLogo $logo): void
    {
        $this->logo = $logo;
    }
}
