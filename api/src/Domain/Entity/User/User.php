<?php

declare(strict_types=1);

namespace App\Domain\Entity\User;

use App\Domain\Entity\Common\TimestampableTrait;
use App\Domain\Entity\Common\UuidTrait;
use App\Domain\Guard\DomainGuard;
use App\Domain\Payload\User\UserPayload;
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
        private(set) Name $name,

        #[ORM\Embedded(columnPrefix: false)]
        private(set) Contact $contact,

        #[ORM\Embedded]
        private(set) Company $company,

        #[ORM\Embedded]
        private(set) CompanyLogo $companyLogo,

        #[ORM\Column(length: 180, unique: true)]
        private(set) string $userIdentifier {
            set {
                $value = DomainGuard::nonEmpty($value, 'User identifier');
                $this->userIdentifier = DomainGuard::email($value, 'User identifier');
            }
        },

        /** @var array<int, string> */
        #[ORM\Column]
        private(set) array $roles {
            get => array_unique([...$this->roles, 'ROLE_USER']);
            set => array_unique($value);
        },

        #[ORM\Column]
        private(set) string $password {
            set => DomainGuard::nonEmpty($value, 'Password');
        },

        #[ORM\Column(length: 10)]
        private(set) string $locale {
            set => DomainGuard::nonEmpty($value, 'Locale');
        },
    ) {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function applyPayload(UserPayload $payload): void
    {
        $this->name = $payload->name;
        $this->contact = $payload->contact;
        $this->company = $payload->company;
        $this->userIdentifier = $payload->userIdentifier;
        $this->locale = $payload->locale;
    }

    public function updatePassword(string $password): void
    {
        $this->password = $password;
    }

    public function updateCompanyLogo(CompanyLogo $companyLogo): void
    {
        $this->companyLogo = $companyLogo;
    }
}
