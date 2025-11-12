<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class ConfigurableKernel extends BaseKernel
{
    use MicroKernelTrait {
        MicroKernelTrait::configureContainer as private traitConfigureContainer;
    }

    private array $parameters = [];

    private array $config = [];

    public function clearCache(): void
    {
        $cacheDir = $this->getCacheDir();

        $filesystem = new Filesystem();
        if ($filesystem->exists($cacheDir)) {
            $filesystem->remove($cacheDir);
        }
    }

    public function getCacheDir(): string
    {
        return parent::getCacheDir().DIRECTORY_SEPARATOR.'test_kernel';
    }

    public function setParameter(string $key, mixed $value): static
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    public function addExtensionConfig(string $extension, array $config): static
    {
        $this->config[$extension] = $config;

        return $this;
    }

    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $this->traitConfigureContainer($container, $loader, $builder);

        // Add parameters
        foreach ($this->parameters as $key => $parameter) {
            $container->parameters()->set($key, $parameter);
        }

        // Add bundles configuration
        foreach ($this->config as $extension => $config) {
            $container->extension($extension, $config);
        }
    }
}
