<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service;

use App\Domain\Contracts\NumberSequenceRepositoryInterface;
use App\Domain\Entity\Numbering\NumberSequence;
use App\Domain\Enum\DocumentType;
use App\Domain\Service\DocumentReferenceGenerator;
use App\Tests\Unit\Domain\Entity\Numbering\NumberSequenceTest;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class DocumentReferenceGeneratorTest extends TestCase
{
    private DocumentReferenceGenerator $generator;

    private InMemorySequenceRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemorySequenceRepository();
        $this->generator = new DocumentReferenceGenerator($this->repository);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function test_sequence_is_created_and_reference_is_formatted(): void
    {
        $reference = $this->generator->generate(DocumentType::INVOICE, 2026);

        static::assertSame('INV-2026-0001', $reference);

        $stored = $this->repository->findOneByTypeAndYear(DocumentType::INVOICE, 2026);
        static::assertNotNull($stored);
        static::assertSame(2, $stored->nextValue);
    }

    public function test_subsequent_number_is_reserved(): void
    {
        $this->generator->generate(DocumentType::QUOTE, 2025, padding: 3);
        $reference = $this->generator->generate(DocumentType::QUOTE, 2025, padding: 3);

        static::assertSame('Q-2025-002', $reference);

        $stored = $this->repository->findOneByTypeAndYear(DocumentType::QUOTE, 2025);
        static::assertNotNull($stored);
        static::assertSame(3, $stored->nextValue);
    }

    #[DataProviderExternal(NumberSequenceTest::class, 'documentTypesProvider')]
    public function test_invalid_year_is_rejected(DocumentType $documentType): void
    {
        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage('Year must be a four-digit value.');

        $this->generator->generate($documentType, 999);
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
        $key = $this->key($sequence->documentType, $sequence->year);
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
