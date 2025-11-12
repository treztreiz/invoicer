<?php

declare(strict_types=1);

namespace App\Tests\Factory\Document;

use App\Domain\Entity\Document\Invoice;
use App\Tests\Factory\Common\BuildableFactoryTrait;
use App\Tests\Factory\Document\Invoice\InstallmentPlanFactory;
use App\Tests\Factory\Document\Invoice\InvoiceRecurrenceFactory;
use Symfony\Component\Uid\Uuid;

/** @extends DocumentFactory<Invoice> */
class InvoiceFactory extends DocumentFactory
{
    use BuildableFactoryTrait;

    public static function class(): string
    {
        return Invoice::class;
    }

    public function withRecurrence(): self
    {
        return $this->with(['recurrence' => InvoiceRecurrenceFactory::new()]);
    }

    public function withInstallmentPlan(): self
    {
        return $this->with(['installmentPlan' => InstallmentPlanFactory::new()]);
    }

    public function generatedFromRecurrence(): self
    {
        return $this->with(['recurrenceSeedId' => Uuid::v7()]);
    }

    public function generatedFromInstallment(): self
    {
        return $this->with(['installmentSeedId' => Uuid::v7()]);
    }
}
