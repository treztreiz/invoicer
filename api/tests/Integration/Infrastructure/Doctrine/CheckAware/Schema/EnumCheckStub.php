<?php

namespace App\Tests\Integration\Infrastructure\Doctrine\CheckAware\Schema;

use App\Infrastructure\Doctrine\CheckAware\Attribute\EnumCheck;
use Doctrine\ORM\Mapping as ORM;

#[EnumCheck(property: 'status')]
#[EnumCheck(property: 'legacyStatus', name: 'CHK_ENUM_LEGACY', enumClass: EnumStatusStub::class)]
#[ORM\Entity]
#[ORM\Table(name: 'enum_check_stub')]
class EnumCheckStub
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    public EnumStatusStub $status = EnumStatusStub::Draft;

    #[ORM\Column]
    public string $legacyStatus = 'draft';
}

enum EnumStatusStub: string
{
    case Draft = 'draft';
    case Issued = 'issued';
}