<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Stub;

use App\Application\Service\Workflow\DocumentWorkflowManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\WorkflowInterface;

class WorkflowManagerStub extends TestCase
{
    public static function create(
        ?WorkflowInterface $quoteWorkflow = null,
        ?WorkflowInterface $invoiceWorkflow = null,
    ): DocumentWorkflowManager {
        $manager = new DocumentWorkflowManager();
        $manager->setQuoteWorkflow($quoteWorkflow ?: static::createStub(WorkflowInterface::class));
        $manager->setInvoiceWorkflow($invoiceWorkflow ?: static::createStub(WorkflowInterface::class));

        return $manager;
    }
}
