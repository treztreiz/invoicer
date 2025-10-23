<?php

namespace App\Domain\Entity\Common;

use Doctrine\ORM\Mapping as ORM;

trait ArchivableTrait
{
    #[ORM\Column]
    private bool $archived = false;

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): static
    {
        $this->archived = $archived;

        return $this;
    }
}