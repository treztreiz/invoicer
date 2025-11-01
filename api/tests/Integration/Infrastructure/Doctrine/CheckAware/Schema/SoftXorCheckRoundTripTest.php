<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Doctrine\CheckAware\Schema;

use App\Infrastructure\Doctrine\CheckAware\Enum\CheckOption;
use App\Infrastructure\Doctrine\CheckAware\Spec\SoftXorCheckSpec;
use App\Tests\TestKernel;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @testType integration
 */
final class SoftXorCheckRoundTripTest extends KernelTestCase
{
    use ResetDatabase;

    protected static ?string $class = TestKernel::class;

    private EntityManagerInterface $entityManager;

    protected static function createKernel(array $options = []): KernelInterface
    {
        /** @var TestKernel $kernel */
        $kernel = parent::createKernel($options);
        $kernel->addExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'SoftXorTest' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => __DIR__,
                        'prefix' => __NAMESPACE__,
                    ],
                ],
            ],
        ]);

        return $kernel;
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @throws SchemaException
     */
    public function test_schema_round_trip_matches_metadata(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);

        $metadata = array_values(
            array_filter(
                $this->entityManager->getMetadataFactory()->getAllMetadata(),
                static fn (ClassMetadata $class): bool => str_starts_with($class->getName(), __NAMESPACE__.'\\'),
            )
        );

        static::assertNotEmpty($metadata, 'Expected stub metadata to be registered.');

        $schema = $schemaTool->getSchemaFromMetadata($metadata);
        $table = $schema->getTable('soft_xor_stub');
        $checks = $table->getOption(CheckOption::DESIRED->value);

        static::assertNotEmpty($checks, 'Soft XOR stub table should declare checks in metadata.');
        static::assertTrue(
            self::containsSoftXorSpec($checks),
            'Metadata should include the Soft XOR constraint definition.',
        );

        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);

        $updateSql = $schemaTool->getUpdateSchemaSql($metadata);

        static::assertSame([], $updateSql, 'Schema update SQL should be empty after round trip.');
    }

    private static function containsSoftXorSpec(array $checks): bool
    {
        return array_any($checks, fn ($spec) => $spec instanceof SoftXorCheckSpec && 'TEST_SOFT_XOR' === $spec->name);
    }
}
