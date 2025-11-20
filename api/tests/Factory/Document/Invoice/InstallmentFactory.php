<?php

declare(strict_types=1);

namespace App\Tests\Factory\Document\Invoice;

use App\Domain\Entity\Document\Invoice\Installment;
use App\Tests\Factory\Common\BuildableFactoryTrait;
use App\Tests\Factory\ValueObject\AmountBreakdownFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/** @extends PersistentObjectFactory<Installment> */
class InstallmentFactory extends PersistentObjectFactory
{
    use BuildableFactoryTrait;

    #[\Override]
    public static function class(): string
    {
        return Installment::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'installmentPlan' => InstallmentPlanFactory::new(),
            'position' => 0,
            'percentage' => '100',
            'amount' => AmountBreakdownFactory::new(),
        ];
    }
}
