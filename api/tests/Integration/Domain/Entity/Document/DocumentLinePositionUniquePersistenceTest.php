<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Entity\Document;

use App\Tests\Factory\Document\DocumentLineFactory;
use App\Tests\Factory\Document\Invoice\InvoiceFactory;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use function Zenstruck\Foundry\Persistence\flush_after;

/**
 * @testType integration
 */
final class DocumentLinePositionUniquePersistenceTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function test_duplicate_line_position_is_rejected(): void
    {
        $invoice = InvoiceFactory::createOne();

        $this->expectException(DriverException::class);
        $this->expectExceptionMessageMatches('/UNIQ_DOCUMENT_LINE_DOCUMENT_POSITION/i');

        flush_after(function () use ($invoice) {
            DocumentLineFactory::createOne(['document' => $invoice, 'position' => 0]);
            DocumentLineFactory::createOne(['document' => $invoice, 'position' => 0]);
        });
    }
}
