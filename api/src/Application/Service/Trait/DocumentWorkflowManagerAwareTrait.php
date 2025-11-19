<?php

declare(strict_types=1);

namespace App\Application\Service\Trait;

use App\Application\Service\Workflow\DocumentWorkflowManager;
use Symfony\Contracts\Service\Attribute\Required;

trait DocumentWorkflowManagerAwareTrait
{
    protected ?DocumentWorkflowManager $documentWorkflowManager = null;

    #[Required]
    public function setDocumentWorkflowManager(DocumentWorkflowManager $documentWorkflowManager): void
    {
        $this->documentWorkflowManager = $documentWorkflowManager;
    }
}
