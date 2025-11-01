<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\Attributes\BeforeClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ConfigurableKernelTestCase extends KernelTestCase
{
    #[BeforeClass]
    public static function clearKernelCache(array $options = []): void
    {
        $options['debug'] = false;

        /** @var TestKernel $kernel */
        $kernel = parent::createKernel($options);

        // Clear the cache to ensure container recompilation
        $kernel->clearCache();
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected static function createKernel(array $options = []): TestKernel
    {
        $options['debug'] = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? false;

        /** @var TestKernel $kernel */
        $kernel = parent::createKernel($options);
        static::applyKernelConfiguration($kernel);

        return $kernel;
    }

    abstract protected static function setKernelConfiguration(TestKernel $kernel): iterable;

    protected static function applyKernelConfiguration(TestKernel $kernel): void
    {
        $options = static::setKernelConfiguration($kernel);
        if (empty($options)) {
            return;
        }

        // Add bundles configuration
        foreach ($options as $extension => $config) {
            $kernel->addExtensionConfig($extension, $config);
        }
    }
}
