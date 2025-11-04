<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Entity\Document;

use App\Domain\Entity\Document\DocumentLine;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Enum\RateUnit;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;
use App\Domain\ValueObject\VatRate;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @testType integration
 */
final class DocumentLineRateUnitEnumPersistenceTest extends KernelTestCase
{
    use ResetDatabase;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @throws \ReflectionException
     * @throws Exception
     */
    public function test_document_line_rate_unit_constraint_blocks_invalid_value(): void
    {
        $invoice = $this->createInvoice();
        $line = $this->createDocumentLine($invoice);
        $this->registerLine($invoice, $line);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->expectException(DriverException::class);

        try {
            $this->entityManager->getConnection()->executeStatement(
                'UPDATE document_line SET rate_unit = :rate WHERE id = :id',
                [
                    'rate' => 'INVALID',
                    'id' => $line->id->toRfc4122(),
                ]
            );
        } catch (DriverException $exception) {
            static::assertSame('23514', $exception->getSQLState());
            static::assertStringContainsString('CHK_DOCUMENT_LINE_RATE_UNIT', $exception->getMessage());
            throw $exception;
        }
    }

    private function createInvoice(): Invoice
    {
        return new Invoice(
            title: 'Document line invoice',
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

    private function createDocumentLine(Invoice $invoice): DocumentLine
    {
        return new DocumentLine(
            document: $invoice,
            description: 'Consulting day',
            quantity: new Quantity('1'),
            rateUnit: RateUnit::DAILY,
            rate: new Money('800'),
            amount: new AmountBreakdown(
                net: new Money('800'),
                tax: new Money('160'),
                gross: new Money('960'),
            ),
            position: 1,
        );
    }

    /**
     * @throws \ReflectionException
     */
    private function registerLine(Invoice $invoice, DocumentLine $line): void
    {
        $parent = (new \ReflectionClass($invoice))->getParentClass();

        if (!$parent instanceof \ReflectionClass) {
            throw new \RuntimeException('Invoice parent class not found.');
        }

        $method = $parent->getMethod('registerLine');
        $method->setAccessible(true);
        $method->invoke($invoice, $line);
    }
}
