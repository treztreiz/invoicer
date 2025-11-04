<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Entity\Document;

use App\Domain\Entity\Document\Invoice;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VatRate;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @testType integration
 */
final class InvoiceStatusEnumPersistenceTest extends KernelTestCase
{
    use ResetDatabase;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @throws Exception
     */
    public function test_invoice_status_constraint_blocks_invalid_value(): void
    {
        $invoice = $this->createInvoice();
        $invoice->issue(new \DateTimeImmutable('2025-01-01'), new \DateTimeImmutable('2025-02-01'));

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->expectException(DriverException::class);

        try {
            $this->entityManager->getConnection()->executeStatement(
                'UPDATE invoice SET status = :status WHERE id = :id',
                [
                    'status' => 'INVALID',
                    'id' => $invoice->id->toRfc4122(),
                ]
            );
        } catch (DriverException $exception) {
            static::assertSame('23514', $exception->getSQLState());
            static::assertStringContainsString('CHK_INVOICE_STATUS', $exception->getMessage());
            throw $exception;
        }
    }

    private function createInvoice(): Invoice
    {
        return new Invoice(
            title: 'Enum invoice',
            currency: 'EUR',
            vatRate: new VatRate('20'),
            total: new AmountBreakdown(
                net: new Money('100'),
                tax: new Money('20'),
                gross: new Money('120'),
            ),
            customerSnapshot: ['name' => 'Client'],
            companySnapshot: ['name' => 'Company'],
        );
    }
}
