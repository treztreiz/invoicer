<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Entity\Document;

use App\Tests\Factory\Document\DocumentLineFactory;
use App\Tests\Factory\Document\InvoiceFactory;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @testType integration
 */
final class DocumentLineRateUnitEnumPersistenceTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function test_document_line_rate_unit_constraint_blocks_invalid_value(): void
    {
        $line = DocumentLineFactory::createOne([
            'document' => InvoiceFactory::new(),
        ]);

        $this->expectException(DriverException::class);

        try {
            $em = self::getContainer()->get(EntityManagerInterface::class);
            $em->getConnection()->executeStatement(
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
}
