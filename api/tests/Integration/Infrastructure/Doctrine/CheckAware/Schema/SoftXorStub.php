<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Doctrine\CheckAware\Schema;

use App\Infrastructure\Doctrine\CheckAware\Attribute\SoftXorCheck;
use Doctrine\ORM\Mapping as ORM;

#[SoftXorCheck(properties: ['firstOption', 'secondOption'], name: 'TEST_SOFT_XOR')]
#[ORM\Entity]
#[ORM\Table(name: 'soft_xor_stub')]
class SoftXorStub
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?string $firstOption = null;

    #[ORM\Column(nullable: true)]
    private ?string $secondOption = null;
}
