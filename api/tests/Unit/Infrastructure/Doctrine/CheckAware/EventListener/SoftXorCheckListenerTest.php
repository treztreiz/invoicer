<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\EventListener;

use App\Infrastructure\Doctrine\CheckAware\Attribute\SoftXorCheck;
use App\Infrastructure\Doctrine\CheckAware\EventListener\SoftXorCheckListener;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckRegistry;
use App\Infrastructure\Doctrine\CheckAware\Spec\SoftXorCheckSpec;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Mapping\JoinColumnMapping;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\OneToOneInverseSideMapping;
use Doctrine\ORM\Mapping\OneToOneOwningSideMapping;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @testType sociable-unit
 */
final class SoftXorCheckListenerTest extends TestCase
{
    /**
     * @throws MappingException
     * @throws SchemaException
     */
    public function test_listener_appends_soft_xor_spec(): void
    {
        $metadata = self::newMetadata();
        $metadata->associationMappings['recurrence'] = self::owningOneToOne('recurrence_id');
        $metadata->associationMappings['installmentPlan'] = self::owningOneToOne('installment_plan_id');

        $schema = static::createStub(Schema::class);

        $table = new Table('invoice');
        $table->addColumn('recurrence_id', 'integer');
        $table->addColumn('installment_plan_id', 'integer');

        $event = new GenerateSchemaTableEventArgs($metadata, $schema, $table);

        $manager = static::createMock(CheckRegistry::class);
        $manager->expects(static::once())
            ->method('appendDeclaredSpec')
            ->with(
                static::identicalTo($table),
                static::callback(static fn ($spec): bool => $spec instanceof SoftXorCheckSpec
                    && 'SOFT_XOR' === $spec->name
                    && $spec->columns === ['recurrence_id', 'installment_plan_id']),
            );

        $listener = new SoftXorCheckListener($manager);
        $listener->postGenerateSchemaTable($event);
    }

    /**
     * @throws MappingException
     * @throws SchemaException
     */
    #[DataProvider('invalidAssociationProvider')]
    public function test_listener_rejects_invalid_association(callable $configure, string $expectedMessage): void
    {
        static::expectException(\LogicException::class);
        static::expectExceptionMessage($expectedMessage);

        $metadata = self::newMetadata();
        $configure($metadata);

        $schema = static::createStub(Schema::class);

        $listener = new SoftXorCheckListener(new CheckRegistry(new CheckNormalizer()));
        $listener->postGenerateSchemaTable(
            new GenerateSchemaTableEventArgs(
                $metadata,
                $schema,
                new Table('invoice'),
            )
        );
    }

    /**
     * @return iterable<string, array{callable(ClassMetadata<object>):void, string}>
     */
    public static function invalidAssociationProvider(): iterable
    {
        yield 'inverse side' => [
            function (ClassMetadata $metadata): void {
                $inverse = self::inverseOneToOne();
                $metadata->associationMappings['recurrence'] = $inverse;
                $metadata->associationMappings['installmentPlan'] = self::owningOneToOne('installment_plan_id');
            },
            'Property "recurrence" on '.DummyInvoice::class.' is inverse-side; make it owning so its FK lives on table "invoice".',
        ];

        yield 'missing join column' => [
            function (ClassMetadata $metadata): void {
                $broken = self::owningOneToOne('');
                $broken->joinColumns = [];
                $metadata->associationMappings['recurrence'] = $broken;
                $metadata->associationMappings['installmentPlan'] = self::owningOneToOne('installment_plan_id');
            },
            'Owning association "recurrence" has no join column mapping.',
        ];
    }

    /**
     * @return ClassMetadata<object>
     */
    private static function newMetadata(): ClassMetadata
    {
        $metadata = new ClassMetadata(DummyInvoice::class);
        $metadata->reflClass = new \ReflectionClass(DummyInvoice::class);
        $metadata->identifier = ['id'];
        $metadata->fieldMappings['id'] = new FieldMapping('integer', 'id', 'id');
        $metadata->setPrimaryTable(['name' => 'invoice']);

        return $metadata;
    }

    private static function owningOneToOne(string $column): OneToOneOwningSideMapping
    {
        $mapping = new OneToOneOwningSideMapping(
            'dummy',
            DummyInvoice::class,
            DummyInvoice::class
        );
        $mapping->joinColumns[] = new JoinColumnMapping(name: $column, referencedColumnName: 'id');

        return $mapping;
    }

    private static function inverseOneToOne(): OneToOneInverseSideMapping
    {
        return new OneToOneInverseSideMapping(
            'dummy',
            DummyInvoice::class,
            DummyInvoice::class
        );
    }
}

#[SoftXorCheck(properties: ['recurrence', 'installmentPlan'])]
final class DummyInvoice
{
}
