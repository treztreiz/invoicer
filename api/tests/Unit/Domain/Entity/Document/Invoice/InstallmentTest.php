<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Document\Invoice;

use App\Domain\Entity\Document\Invoice\Installment;
use App\Domain\Exception\DomainGuardException;
use App\Domain\Payload\Invoice\Installment\ComputedInstallmentPayload;
use App\Domain\Payload\Invoice\Installment\InstallmentPayload;
use App\Tests\Factory\Document\Invoice\InstallmentPlanFactory;
use App\Tests\Factory\ValueObject\AmountBreakdownFactory;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Test\Factories;

class InstallmentTest extends TestCase
{
    use Factories;

    public function test_add_installment_rejects_negative_position(): void
    {
        $this->expectException(DomainGuardException::class);

        $this->createInstallment(position: -1);
    }

    public function test_add_installment_rejects_percentage_above_100(): void
    {
        $this->expectException(DomainGuardException::class);

        $this->createInstallment(percentage: '110');
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function createInstallment(int $position = 0, string $percentage = '100'): Installment
    {
        $payload = new ComputedInstallmentPayload(
            payload: new InstallmentPayload(
                id: null,
                percentage: $percentage,
                dueDate: null,
            ),
            position: $position,
            amount: AmountBreakdownFactory::createOne()
        );

        return Installment::fromPayload($payload, InstallmentPlanFactory::build()->create());
    }
}
