<?php

declare(strict_types=1);

namespace App\Domain\Entity\Common;

use Doctrine\ORM\Mapping as ORM;

trait ArchivableTrait
{
    #[ORM\Column]
    protected(set) bool $isArchived = false;

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function archive(): static
    {
        $this->isArchived = true;

        return $this;
    }

    public function unarchive(): static
    {
        $this->isArchived = false;

        return $this;
    }
}
