<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Doctrine\CheckAware\Schema;

use App\Tests\ConfigurableKernel;
use App\Tests\ConfigurableKernelTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @testType integration
 */
final class CheckAwareMigrationDiffTest extends ConfigurableKernelTestCase
{
    use ResetDatabase;

    private Filesystem $filesystem;

    private EntityManagerInterface $entityManager;

    private string $migrationsDir;

    protected static function setKernelConfiguration(ConfigurableKernel $kernel): iterable
    {
        yield 'doctrine' => [
            'orm' => [
                'mappings' => [
                    'CheckAwareTests' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => __DIR__,
                        'prefix' => __NAMESPACE__,
                    ],
                ],
            ],
        ];

        yield 'doctrine_migrations' => [
            'migrations_paths' => [
                'DoctrineMigrations' => $kernel->getCacheDir().'/test_migrations',
            ],
        ];
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $this->migrationsDir = static::$kernel->getCacheDir().'/test_migrations';

        $this->filesystem = new Filesystem();
        if (false === $this->filesystem->exists($this->migrationsDir)) {
            $this->filesystem->mkdir($this->migrationsDir);
        }
    }

    /**
     * @param non-empty-string $filterExpression
     * @param non-empty-string $expectedConstraint
     * @param non-empty-string $expectedSnippet
     *
     * @throws \Exception
     */
    #[DataProvider('migrationDiffProvider')]
    public function test_migration_diff_contains_check_once(
        string $filterExpression,
        string $expectedConstraint,
        string $expectedSnippet,
    ): void {
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropDatabase();

        $firstDiffOutput = $this->runDiffCommand($filterExpression);

        $generatedMigrations = glob($this->migrationsDir.'/*.php') ?: [];
        static::assertCount(1, $generatedMigrations, $firstDiffOutput);

        $migrationContents = file_get_contents($generatedMigrations[0]) ?: '';
        static::assertStringContainsString($expectedConstraint, $migrationContents);
        static::assertStringContainsString(strtoupper($expectedSnippet), strtoupper($migrationContents));
        static::assertSame(
            1,
            substr_count($migrationContents, sprintf('ADD CONSTRAINT "%s"', $expectedConstraint)),
            'Expected constraint should be added exactly once.'
        );

        $this->filesystem->remove($generatedMigrations);

        $metadata = $this->filteredMetadata();
        $schemaTool->createSchema($metadata);

        $secondDiffOutput = $this->runDiffCommand($filterExpression);
        $generatedMigrations = glob($this->migrationsDir.'/*.php') ?: [];

        static::assertCount(0, $generatedMigrations, $secondDiffOutput);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function migrationDiffProvider(): iterable
    {
        yield 'soft_xor' => [
            '/^soft_xor_check_stub$/',
            'TEST_SOFT_XOR',
            'num_nonnulls',
        ];

        yield 'enum_check' => [
            '/^enum_check_stub$/',
            'CHK_ENUM_LEGACY',
            'ALTER TABLE enum_check_stub ADD constraint "CHK_ENUM_LEGACY"',
        ];
    }

    /**
     * @return list<ClassMetadata<object>>
     */
    private function filteredMetadata(): array
    {
        /** @var list<ClassMetadata<object>> $metadata */
        $metadata = array_values(
            array_filter(
                $this->entityManager->getMetadataFactory()->getAllMetadata(),
                static fn (ClassMetadata $class): bool => str_starts_with($class->getName(), __NAMESPACE__.'\\')
            )
        );

        static::assertNotEmpty($metadata, 'Expected stub metadata to be registered.');

        return $metadata;
    }

    /**
     * @param non-empty-string $filterExpression
     *
     * @throws \Exception
     */
    private function runDiffCommand(string $filterExpression): string
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'doctrine:migrations:diff',
            '--env' => 'test',
            '--no-interaction' => true,
            '--filter-expression' => $filterExpression,
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);

        return $output->fetch();
    }
}
