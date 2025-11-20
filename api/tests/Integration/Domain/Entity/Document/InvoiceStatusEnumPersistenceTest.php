<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Entity\Document;

use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @testType integration
 */
final class InvoiceStatusEnumPersistenceTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function test_invoice_status_constraint_blocks_invalid_value(): void
    {
        $invoice = \App\Tests\Factory\Document\Invoice\InvoiceFactory::createOne();

        $this->expectException(DriverException::class);

        try {
            $em = self::getContainer()->get(EntityManagerInterface::class);
            $em->getConnection()->executeStatement(
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
}
