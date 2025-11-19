<?php

declare(strict_types=1);

namespace App\Application\Service\Trait;

use App\Application\Service\Document\DocumentSnapshotFactory;
use Symfony\Contracts\Service\Attribute\Required;

trait DocumentSnapshotFactoryAwareTrait
{
    protected ?DocumentSnapshotFactory $documentSnapshotFactory = null;

    #[Required]
    public function setDocumentSnapshotFactory(DocumentSnapshotFactory $documentSnapshotFactory): void
    {
        $this->documentSnapshotFactory = $documentSnapshotFactory;
    }
}
