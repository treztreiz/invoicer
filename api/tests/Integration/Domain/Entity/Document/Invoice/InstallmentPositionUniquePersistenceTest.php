<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Entity\Document\Invoice;

use App\Tests\Factory\Document\Invoice\InstallmentFactory;
use App\Tests\Factory\Document\Invoice\InstallmentPlanFactory;
use App\Tests\Factory\Document\Invoice\InvoiceFactory;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function Zenstruck\Foundry\Persistence\flush_after;

/**
 * @testType integration
 */
final class InstallmentPositionUniquePersistenceTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function test_duplicate_installment_position_is_rejected(): void
    {
        $plan = flush_after(function () {
            $invoice = InvoiceFactory::createOne();

            return InstallmentPlanFactory::createOne(['invoice' => $invoice]);
        });

        $this->expectException(DriverException::class);
        $this->expectExceptionMessageMatches('/UNIQ_INSTALLMENT_PLAN_POSITION/i');

        flush_after(function () use ($plan) {
            InstallmentFactory::createOne(['installmentPlan' => $plan, 'position' => 0]);
            InstallmentFactory::createOne(['installmentPlan' => $plan, 'position' => 0]);
        });
    }
}
