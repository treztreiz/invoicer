<?php

declare(strict_types=1);

namespace App\Application\Workflow;

use Symfony\Component\Workflow\WorkflowInterface;

final class WorkflowActionsHelper
{
    /**
     * @return list<string>
     */
    public function availableActions(object $subject, WorkflowInterface $workflow): array
    {
        return array_map(
            static fn ($transition) => $transition->getName(),
            $workflow->getEnabledTransitions($subject)
        );
    }
}
