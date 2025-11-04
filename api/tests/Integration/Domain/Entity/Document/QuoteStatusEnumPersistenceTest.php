<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Entity\Document;

use App\Domain\Entity\Document\Quote;
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
final class QuoteStatusEnumPersistenceTest extends KernelTestCase
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
    public function test_quote_status_constraint_blocks_invalid_value(): void
    {
        $quote = $this->createQuote();
        $quote->send(new \DateTimeImmutable('2025-03-01'));

        $this->entityManager->persist($quote);
        $this->entityManager->flush();

        $this->expectException(DriverException::class);

        try {
            $this->entityManager->getConnection()->executeStatement(
                'UPDATE quote SET status = :status WHERE id = :id',
                [
                    'status' => 'INVALID',
                    'id' => $quote->id->toRfc4122(),
                ]
            );
        } catch (DriverException $exception) {
            static::assertSame('23514', $exception->getSQLState());
            static::assertStringContainsString('CHK_QUOTE_STATUS', $exception->getMessage());
            throw $exception;
        }
    }

    private function createQuote(): Quote
    {
        return new Quote(
            title: 'Enum quote',
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
