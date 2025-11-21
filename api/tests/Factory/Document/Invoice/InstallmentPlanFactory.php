<?php

declare(strict_types=1);

namespace App\Tests\Factory\Document\Invoice;

use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use App\Tests\Factory\Common\BuildableFactoryTrait;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/** @extends PersistentObjectFactory<InstallmentPlan> */
class InstallmentPlanFactory extends PersistentObjectFactory
{
    use BuildableFactoryTrait;

    #[\Override]
    public static function class(): string
    {
        return InstallmentPlan::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [];
    }
}
