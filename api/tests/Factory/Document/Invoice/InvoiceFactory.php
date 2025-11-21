<?php

declare(strict_types=1);

namespace App\Tests\Factory\Document\Invoice;

use App\Domain\Entity\Document\Invoice\Invoice;
use App\Domain\Enum\InvoiceStatus;
use App\Tests\Factory\Common\BuildableFactoryTrait;
use App\Tests\Factory\Document\DocumentFactory;
use Symfony\Component\Uid\Uuid;

/** @extends DocumentFactory<Invoice> */
class InvoiceFactory extends DocumentFactory
{
    use BuildableFactoryTrait;

    #[\Override]
    public static function class(): string
    {
        return Invoice::class;
    }

    public function draft(): self
    {
        return $this->with(['status' => InvoiceStatus::DRAFT]);
    }

    public function issued(): self
    {
        return $this->with(['status' => InvoiceStatus::ISSUED]);
    }

    public function overdue(): self
    {
        return $this->with(['status' => InvoiceStatus::OVERDUE]);
    }

    public function paid(): self
    {
        return $this->with(['status' => InvoiceStatus::PAID]);
    }

    public function voided(): self
    {
        return $this->with(['status' => InvoiceStatus::VOIDED]);
    }

    public function withRecurrence(): self
    {
        return $this->with(['recurrence' => RecurrenceFactory::build()]);
    }

    public function withInstallmentPlan(int $numberOfInstallments = 0): self
    {
        $installmentPlan = InstallmentPlanFactory::build();
        $installments = InstallmentFactory::build([
            'installmentPlan' => $installmentPlan,
        ])->sequence(static function () use ($numberOfInstallments) {
            for ($i = 0; $i < $numberOfInstallments; ++$i) {
                yield ['position' => $i];
            }
        });

        return $this->with([
            'installmentPlan' => $installmentPlan->with([
                'installments' => $installments,
            ]),
        ]);
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
