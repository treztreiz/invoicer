<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Doctrine\CheckAware\Schema;

use App\Infrastructure\Doctrine\CheckAware\Attribute\SoftXorCheck;
use Doctrine\ORM\Mapping as ORM;

#[SoftXorCheck(properties: ['firstProperty', 'secondProperty'], name: 'TEST_SOFT_XOR')]
#[ORM\Entity]
#[ORM\Table(name: 'soft_xor_check_stub')]
class SoftXorCheckStub
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?string $firstProperty = null;

    #[ORM\Column(nullable: true)]
    private ?string $secondProperty = null;
}
