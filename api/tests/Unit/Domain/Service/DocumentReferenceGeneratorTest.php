<?php

namespace App\Tests\Unit\Domain\Service;

use App\Domain\Contracts\NumberSequenceRepositoryInterface;
use App\Domain\Entity\Numbering\NumberSequence;
use App\Domain\Enum\DocumentType;
use App\Domain\Service\DocumentReferenceGenerator;
use PHPUnit\Framework\TestCase;

final class DocumentReferenceGeneratorTest extends TestCase
{
    private DocumentReferenceGenerator $generator;

    private InMemorySequenceRepository $repository;

    public function setUp(): void
    {
        $this->repository = new InMemorySequenceRepository();
        $this->generator = new DocumentReferenceGenerator($this->repository);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function test_sequence_is_created_and_reference_is_formatted(): void
    {
        $reference = $this->generator->generate(DocumentType::INVOICE, 2026);

        self::assertSame('INV-2026-0001', $reference);

        $stored = $this->repository->findOneByTypeAndYear(DocumentType::INVOICE, 2026);
        self::assertNotNull($stored);
        self::assertSame(2, $stored->nextValue());
    }

    public function test_subsequent_number_is_reserved(): void
    {
        $this->generator->generate(DocumentType::QUOTE, 2025, padding: 3);
        $reference = $this->generator->generate(DocumentType::QUOTE, 2025, padding: 3);

        self::assertSame('Q-2025-002', $reference);

        $stored = $this->repository->findOneByTypeAndYear(DocumentType::QUOTE, 2025);
        self::assertNotNull($stored);
        self::assertSame(3, $stored->nextValue());
    }

    public function test_invalid_year_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Year must be a four-digit value.');

        $this->generator->generate(DocumentType::INVOICE, 999);
    }
}

// /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Simple in-memory implementation for unit testing.
 */
final class InMemorySequenceRepository implements NumberSequenceRepositoryInterface
{
    /** @var array<string, NumberSequence> */
    private array $storage = [];

    public function save(NumberSequence $sequence): void
    {
        $key = $this->key($sequence->documentType(), $sequence->year());
        $this->storage[$key] = $sequence;
    }

    public function findOneByTypeAndYear(DocumentType $documentType, int $year): ?NumberSequence
    {
        $key = $this->key($documentType, $year);

        return $this->storage[$key] ?? null;
    }

    private function key(DocumentType $type, int $year): string
    {
        return sprintf('%s-%d', $type->value, $year);
    }
}
