<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Enum;

enum ConstraintTiming: string
{
    case IMMEDIATE = 'immediate';
    case DEFERRED_ON_DEMAND = 'deferredOnDemand';
    case DEFERRED_UNTIL_COMMIT = 'deferredUntilCommit';
}
