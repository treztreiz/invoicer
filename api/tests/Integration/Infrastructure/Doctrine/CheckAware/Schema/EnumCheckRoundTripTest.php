<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Doctrine\CheckAware\Schema;

use App\Infrastructure\Doctrine\CheckAware\Enum\CheckOption;
use App\Infrastructure\Doctrine\CheckAware\Spec\EnumCheckSpec;
use App\Tests\ConfigurableKernelTestCase;
use App\Tests\TestKernel;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @testType integration
 */
final class EnumCheckRoundTripTest extends ConfigurableKernelTestCase
{
    use ResetDatabase;

    private EntityManagerInterface $entityManager;

    protected static function setKernelConfiguration(TestKernel $kernel): iterable
    {
        yield 'doctrine' => [
            'orm' => [
                'mappings' => [
                    'EnumCheckTest' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => __DIR__,
                        'prefix' => __NAMESPACE__,
                    ],
                ],
            ],
        ];
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @throws SchemaException
     */
    public function test_enum_check_round_trip_is_idempotent(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);

        $metadata = array_values(
            array_filter(
                $this->entityManager->getMetadataFactory()->getAllMetadata(),
                static fn(ClassMetadata $class): bool => EnumCheckStub::class === $class->getName(),
            )
        );

        static::assertNotEmpty($metadata, 'Expected stub metadata to be registered.');

        $schema = $schemaTool->getSchemaFromMetadata($metadata);
        $table = $schema->getTable('enum_check_stub');
        $checks = $table->getOption(CheckOption::DECLARED->value);

        static::assertNotEmpty($checks, 'Enum check stub table should declare checks in metadata.');
        static::assertTrue(self::containsEnumSpec($checks), 'Metadata should include the enum constraint definition.');

        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);

        $updateSql = $schemaTool->getUpdateSchemaSql($metadata);

        static::assertSame([], $updateSql, 'Schema update SQL should be empty after round trip.');
    }

    private static function containsEnumSpec(array $checks): bool
    {
        return array_any(
            $checks,
            static fn($spec): bool => $spec instanceof EnumCheckSpec && $spec->expr['column'] === 'status'
        );
    }
}
