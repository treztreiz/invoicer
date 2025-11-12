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

        /** @var ConfigurableKernel $kernel */
        $kernel = parent::createKernel($options);

        // Clear the cache to ensure container recompilation
        $kernel->clearCache();
    }

    protected static function getKernelClass(): string
    {
        return ConfigurableKernel::class;
    }

    protected static function createKernel(array $options = []): ConfigurableKernel
    {
        $options['debug'] = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? false;

        /** @var ConfigurableKernel $kernel */
        $kernel = parent::createKernel($options);
        static::applyKernelConfiguration($kernel);

        return $kernel;
    }

    abstract protected static function setKernelConfiguration(ConfigurableKernel $kernel): iterable;

    protected static function applyKernelConfiguration(ConfigurableKernel $kernel): void
    {
        $options = iterator_to_array(static::setKernelConfiguration($kernel));
        if (empty($options)) {
            return;
        }

        // Set parameters
        $parameters = $options['parameters'] ?? null;
        if ($parameters) {
            if (!is_array($parameters)) {
                throw new \InvalidArgumentException('Parameters must be an array');
            }

            foreach ($parameters as $key => $value) {
                $kernel->setParameter($key, $value);
            }

            unset($options['parameters']);
        }

        // Add bundles config
        foreach ($options as $extension => $config) {
            $kernel->addExtensionConfig($extension, $config);
        }
    }
}
