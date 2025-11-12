<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Document;

use App\Domain\DTO\InstallmentPayload;
use App\Domain\DTO\InstallmentPlanPayload;
use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use App\Domain\ValueObject\AmountBreakdown;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class InstallmentPlanTest extends TestCase
{
    public function test_from_payload_creates_installments(): void
    {
        $payload = new InstallmentPlanPayload([
            new InstallmentPayload(
                position: 1,
                percentage: '40.00',
                amount: AmountBreakdown::fromValues('400.00', '80.00', '480.00'),
                dueDate: new \DateTimeImmutable('2025-01-01'),
            ),
            new InstallmentPayload(
                position: 2,
                percentage: '60.00',
                amount: AmountBreakdown::fromValues('600.00', '120.00', '720.00'),
                dueDate: new \DateTimeImmutable('2025-02-01'),
            ),
        ]);

        $plan = InstallmentPlan::fromPayload($payload);
        $installments = $plan->installments();

        static::assertCount(2, $installments);
        static::assertSame(1, $installments[0]->position);
        static::assertSame('40.00', $installments[0]->percentage);
        static::assertSame('480.00', $installments[0]->amount->gross->value);

        static::assertSame(2, $installments[1]->position);
        static::assertSame('60.00', $installments[1]->percentage);
        static::assertSame('720.00', $installments[1]->amount->gross->value);
    }

    public function test_add_installment_rejects_negative_position(): void
    {
        $plan = new InstallmentPlan();

        $this->expectException(\InvalidArgumentException::class);

        $plan->addInstallment(
            position: -1,
            percentage: '50.00',
            amount: AmountBreakdown::fromValues('500.00', '100.00', '600.00')
        );
    }

    public function test_add_installment_rejects_percentage_above_100(): void
    {
        $plan = new InstallmentPlan();

        $this->expectException(\InvalidArgumentException::class);

        $plan->addInstallment(
            position: 0,
            percentage: '150.00',
            amount: AmountBreakdown::fromValues('500.00', '100.00', '600.00')
        );
    }
}
