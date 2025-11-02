<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\EventListener;

use App\Infrastructure\Doctrine\CheckAware\Attribute\EnumCheck;
use App\Infrastructure\Doctrine\CheckAware\EventListener\EnumCheckListener;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckOptionManager;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;
use App\Infrastructure\Doctrine\CheckAware\Spec\EnumCheckSpec;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use PHPUnit\Framework\TestCase;

/**
 * @testType sociable-unit
 */
final class EnumCheckListenerTest extends TestCase
{
    private CheckOptionManager $optionManager;

    private EnumCheckListener $listener;

    protected function setUp(): void
    {
        $this->optionManager = new CheckOptionManager(new CheckNormalizer());
        $this->listener = new EnumCheckListener($this->optionManager);
    }

    /**
     * @throws \ReflectionException
     * @throws MappingException
     * @throws SchemaException
     */
    public function test_listener_appends_enum_spec_for_typed_property(): void
    {
        $metadata = $this->metadataForClass(TypedEnumEntity::class, [
            'status' => FieldMapping::fromMappingArray([
                'type' => 'string',
                'fieldName' => 'status',
                'columnName' => 'status',
                'enumType' => StatusEnum::class,
            ]),
        ]);

        $table = new Table('typed_enum');
        $table->addColumn('status', 'string');

        $args = new GenerateSchemaTableEventArgs($metadata, $this->schemaStub(), $table);

        $this->listener->postGenerateSchemaTable($args);

        $checks = $this->optionManager->desired($table);

        static::assertCount(1, $checks);
        static::assertInstanceOf(EnumCheckSpec::class, $checks[0]);
        static::assertSame(
            ['column' => 'status', 'values' => ['draft', 'issued'], 'is_string' => true],
            $checks[0]->expr
        );
    }

    /**
     * @throws \ReflectionException
     * @throws MappingException
     * @throws SchemaException
     */
    public function test_listener_requires_enum_class_for_untyped_property(): void
    {
        $metadata = $this->metadataForClass(UntypedEnumEntity::class, [
            'status' => FieldMapping::fromMappingArray([
                'type' => 'string',
                'fieldName' => 'status',
                'columnName' => 'status',
            ]),
        ]);

        $table = new Table('untyped_enum');
        $table->addColumn('status', 'string');

        $args = new GenerateSchemaTableEventArgs($metadata, $this->schemaStub(), $table);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('EnumCheck on '.UntypedEnumEntity::class.'::status requires enumClass because the property is not typed as a backed enum.');

        $this->listener->postGenerateSchemaTable($args);
    }

    /**
     * @throws \ReflectionException
     * @throws MappingException
     * @throws SchemaException
     */
    public function test_listener_accepts_explicit_enum_class_for_untyped_property(): void
    {
        $metadata = $this->metadataForClass(UntypedEnumWithAttributeEntity::class, [
            'status' => FieldMapping::fromMappingArray([
                'type' => 'string',
                'fieldName' => 'status',
                'columnName' => 'status',
            ]),
        ]);

        $table = new Table('explicit_enum');
        $table->addColumn('status', 'string');

        $args = new GenerateSchemaTableEventArgs($metadata, $this->schemaStub(), $table);

        $this->listener->postGenerateSchemaTable($args);

        $checks = $this->optionManager->desired($table);
        static::assertCount(1, $checks);
        static::assertSame(['column' => 'status', 'values' => ['draft', 'issued'], 'is_string' => true], $checks[0]->expr);
    }

    /**
     * @throws \ReflectionException
     * @throws MappingException
     * @throws SchemaException
     */
    public function test_listener_rejects_incompatible_column_type(): void
    {
        $metadata = $this->metadataForClass(IntBackedEnumEntity::class, [
            'priority' => FieldMapping::fromMappingArray([
                'type' => 'string',
                'fieldName' => 'priority',
                'columnName' => 'priority',
            ]),
        ]);

        $table = new Table('invalid_enum');
        $table->addColumn('priority', 'string');
        $args = new GenerateSchemaTableEventArgs($metadata, $this->schemaStub(), $table);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('EnumCheck on '.IntBackedEnumEntity::class.'::priority expects an int-backed enum, but Doctrine column type is "string".');

        $this->listener->postGenerateSchemaTable($args);
    }

    /**
     * @param class-string $class
     * @param array<string, FieldMapping> $fieldMappings
     * @throws \ReflectionException
     */
    private function metadataForClass(string $class, array $fieldMappings): ClassMetadata
    {
        $metadata = new ClassMetadata($class);
        $metadata->reflClass = new \ReflectionClass($class);
        $metadata->identifier = ['id'];
        $metadata->fieldMappings['id'] = new FieldMapping('integer', 'id', 'id');
        $metadata->setPrimaryTable(['name' => 'stub']);

        foreach ($fieldMappings as $name => $mapping) {
            $metadata->fieldMappings[$name] = $mapping;
        }

        return $metadata;
    }

    private function schemaStub(): Schema
    {
        return static::createStub(Schema::class);
    }
}

#[EnumCheck(property: 'status')]
final class TypedEnumEntity
{
    public int $id = 0;

    public StatusEnum $status;
}

#[EnumCheck(property: 'status')]
final class UntypedEnumEntity
{
    public int $id = 0;

    public string $status = '';
}

#[EnumCheck(property: 'status', enumClass: StatusEnum::class)]
final class UntypedEnumWithAttributeEntity
{
    public int $id = 0;

    public string $status = '';
}

#[EnumCheck(property: 'priority')]
final class IntBackedEnumEntity
{
    public int $id = 0;

    public PriorityEnum $priority;
}

enum StatusEnum: string
{
    case Draft = 'draft';
    case Issued = 'issued';
}

enum PriorityEnum: int
{
    case Low = 0;
    case High = 1;
}
