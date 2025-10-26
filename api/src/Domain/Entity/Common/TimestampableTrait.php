<?php

namespace App\Domain\Entity\Common;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

trait TimestampableTrait
{
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    protected(set) \DateTimeImmutable $createdAt {
        get => $this->createdAt ?? new \DateTimeImmutable();
    }

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    protected(set) \DateTimeImmutable $updatedAt {
        get => $this->updatedAt ?? new \DateTimeImmutable();
    }
}
