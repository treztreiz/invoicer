<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class TestKernel extends BaseKernel
{
    use MicroKernelTrait {
        MicroKernelTrait::configureContainer as private traitConfigureContainer;
    }

    private bool $deleteCache = true;

    private array $config = [];

    public function shutdown(): void
    {
        parent::shutdown();

        if ($this->deleteCache) {
            new Filesystem()->remove($this->getCacheDir());
        }
    }

    public function setDeleteCache(bool $deleteCache): static
    {
        $this->deleteCache = $deleteCache;

        return $this;
    }

    public function getCacheDir(): string
    {
        return parent::getCacheDir().DIRECTORY_SEPARATOR.'test_kernel';
    }

    public function addExtensionConfig(string $extension, array $config): static
    {
        $this->config[$extension] = $config;

        return $this;
    }

    private function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $this->traitConfigureContainer($container, $loader, $builder);

        // Add bundles configuration
        foreach ($this->config as $extension => $config) {
            $container->extension($extension, $config);
        }
    }
}
