<?php

declare(strict_types=1);

namespace App\Tests\Factory\Document\Invoice;

use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/** @extends PersistentObjectFactory<InstallmentPlan> */
class InstallmentPlanFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return InstallmentPlan::class;
    }

    protected function defaults(): array|callable
    {
        return [];
    }
}
