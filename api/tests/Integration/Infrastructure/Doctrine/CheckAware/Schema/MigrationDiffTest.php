<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Doctrine\CheckAware\Schema;

use App\Tests\TestKernel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @testType integration
 */
final class MigrationDiffTest extends KernelTestCase
{
    use ResetDatabase;

    protected static ?string $class = TestKernel::class;

    private EntityManagerInterface $entityManager;

    private string $migrationsDir;

    private Filesystem $filesystem;

    protected static function createKernel(array $options = []): KernelInterface
    {
        /** @var TestKernel $kernel */
        $kernel = parent::createKernel($options);
        $kernel
            ->addExtensionConfig('doctrine', [
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
            ])
            ->addExtensionConfig('doctrine_migrations', [
                'migrations_paths' => [
                    'DoctrineMigrations' => $kernel->getCacheDir().'/test_migrations',
                ],
            ]);

        return $kernel;
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
     * @throws \Exception
     */
    public function test_migration_diff_contains_soft_xor_check_once(): void
    {
        // Ensure database is empty before generating diff
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropDatabase();

        $firstDiffOutput = $this->runDiffCommand();

        $generatedMigrations = glob($this->migrationsDir.'/*.php');
        static::assertCount(1, $generatedMigrations, $firstDiffOutput);

        $migrationContents = file_get_contents($generatedMigrations[0]);
        static::assertStringContainsString('TEST_SOFT_XOR', $migrationContents);
        static::assertSame(1, substr_count(strtoupper($migrationContents), 'CHECK'), 'Check constraint should appear exactly once.');

        // Clean directory for the second run
        $this->filesystem->remove($generatedMigrations);

        // Bring the database schema in sync with metadata
        $metadata = $this->filteredMetadata();
        $schemaTool->createSchema($metadata);

        $secondDiffOutput = $this->runDiffCommand();
        $generatedMigrations = glob($this->migrationsDir.'/*.php');

        static::assertCount(0, $generatedMigrations, $secondDiffOutput);
    }

    /**
     * @return list<ClassMetadata>
     */
    private function filteredMetadata(): array
    {
        /** @var list<ClassMetadata> $metadata */
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
     * @throws \Exception
     */
    private function runDiffCommand(): string
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'doctrine:migrations:diff',
            '--env' => 'test',
            '--no-interaction' => true,
            '--filter-expression' => '/^soft_xor_stub$/',
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);

        return $output->fetch();
    }
}
