<?php

namespace App\Domain\Entity\Common;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

trait TimestampableTrait
{
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private(set) ?DateTimeImmutable $createdAt = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private(set) ?DateTimeImmutable $updatedAt = null;
}