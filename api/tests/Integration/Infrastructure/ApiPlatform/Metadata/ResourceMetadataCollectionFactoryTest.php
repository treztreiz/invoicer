<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\ApiPlatform\Metadata;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use App\Infrastructure\ApiPlatform\Metadata\ResourceRegistry;
use App\Tests\ConfigurableKernelTestCase;
use App\Tests\Fixtures\ApiPlatform\State\Dummy\DummyStateProcessor;
use App\Tests\Fixtures\ApiPlatform\State\Dummy\DummyStateProvider;
use App\Tests\Fixtures\ApiPlatform\UseCase\Dummy\Command\DummyCommand;
use App\Tests\Fixtures\ApiPlatform\UseCase\Dummy\Result\DummyResult;
use App\Tests\TestKernel;

/**
 * @testType integration
 */
final class ResourceMetadataCollectionFactoryTest extends ConfigurableKernelTestCase
{
    protected static function setKernelConfiguration(TestKernel $kernel): iterable
    {
        yield 'parameters' => [
            'app.api_platform.metadata.output_suffix' => 'Result',
            'app.api_platform.metadata.output_class_template' => 'App\Tests\Fixtures\ApiPlatform\UseCase\%%%%s\Result\%sResult',
            'app.api_platform.metadata.input_class_template' => 'App\Tests\Fixtures\ApiPlatform\UseCase\%%%%s\Command\%sCommand',
            'app.api_platform.metadata.provider_class_template' => 'App\Tests\Fixtures\ApiPlatform\State\%%%%s\%sStateProvider',
            'app.api_platform.metadata.processor_class_template' => 'App\Tests\Fixtures\ApiPlatform\State\%%%%s\%sStateProcessor',
            'app.api_platform.metadata.controller' => 'dummy_controller',
        ];
    }

    /**
     * @throws ResourceClassNotFoundException
     */
    public function test_registry_backed_resources_are_discovered_and_wired(): void
    {
        self::bootKernel();

        $stubRegistry = new ResourceRegistry([
            DummyResult::class => [
                new ApiResource(
                    uriTemplate: '/dummy',
                    operations: [
                        new Get(
                            normalizationContext: ['groups' => ['dummy:read']],
                            name: 'api_dummy_get'
                        ),
                        new Put(
                            normalizationContext: ['groups' => ['dummy:read']],
                            denormalizationContext: ['groups' => ['dummy:write']],
                            name: 'api_dummy_update'
                        ),
                    ],
                    uriVariables: []
                ),
                new ApiResource(
                    uriTemplate: '/dummy/secondary',
                    operations: [
                        new Get(name: 'api_dummy_secondary_get'),
                    ],
                    uriVariables: []
                ),
            ],
        ]);

        self::getContainer()->set(ResourceRegistry::class, $stubRegistry);

        $resourceNames = iterator_to_array($this->resourceNameFactory()->create());
        static::assertContains(DummyResult::class, $resourceNames);

        $metadataCollection = $this->resourceMetadataFactory()->create(DummyResult::class);
        static::assertCount(2, $metadataCollection);

        /** @var ApiResource $primaryResource */
        $primaryResource = $metadataCollection[0];
        static::assertSame(DummyResult::class, $primaryResource->getClass());
        static::assertSame('DummyResult', $primaryResource->getShortName());
        static::assertSame('/dummy', $primaryResource->getUriTemplate());
        static::assertSame([], $primaryResource->getUriVariables());

        $operations = iterator_to_array($primaryResource->getOperations());
        static::assertArrayHasKey('api_dummy_get', $operations);
        static::assertArrayHasKey('api_dummy_update', $operations);

        /** @var HttpOperation $getOperation */
        $getOperation = $operations['api_dummy_get'];
        static::assertSame('GET', $getOperation->getMethod());
        static::assertSame('dummy_controller', $getOperation->getController());
        static::assertSame(DummyStateProvider::class, $getOperation->getProvider());
        static::assertSame(DummyResult::class, $getOperation->getClass());
        static::assertSame('/dummy', $getOperation->getUriTemplate());
        static::assertSame([], $getOperation->getUriVariables());

        /** @var HttpOperation $putOperation */
        $putOperation = $operations['api_dummy_update'];
        static::assertSame('PUT', $putOperation->getMethod());
        static::assertSame('dummy_controller', $putOperation->getController());
        static::assertSame(DummyStateProcessor::class, $putOperation->getProcessor());
        $inputMetadata = $putOperation->getInput();
        static::assertIsArray($inputMetadata);
        static::assertSame(DummyCommand::class, $inputMetadata['class'] ?? null);
        static::assertSame(DummyResult::class, $putOperation->getClass());
        static::assertSame('/dummy', $putOperation->getUriTemplate());
        static::assertSame([], $putOperation->getUriVariables());
        static::assertNotEmpty($putOperation->getInputFormats());

        /** @var ApiResource $secondaryResource */
        $secondaryResource = $metadataCollection[1];
        static::assertSame('/dummy/secondary', $secondaryResource->getUriTemplate());
        $secondaryOperations = iterator_to_array($secondaryResource->getOperations());
        static::assertCount(1, $secondaryOperations);
        static::assertArrayHasKey('api_dummy_secondary_get', $secondaryOperations);

        /** @var HttpOperation $secondaryGet */
        $secondaryGet = $secondaryOperations['api_dummy_secondary_get'];
        static::assertSame('GET', $secondaryGet->getMethod());
        static::assertSame('dummy_controller', $secondaryGet->getController());
        static::assertSame(DummyStateProvider::class, $secondaryGet->getProvider());
    }

    private function resourceNameFactory(): ResourceNameCollectionFactoryInterface
    {
        /** @var ResourceNameCollectionFactoryInterface $factory */
        $factory = self::getContainer()->get(ResourceNameCollectionFactoryInterface::class);

        return $factory;
    }

    private function resourceMetadataFactory(): ResourceMetadataCollectionFactoryInterface
    {
        /** @var ResourceMetadataCollectionFactoryInterface $factory */
        $factory = self::getContainer()->get(ResourceMetadataCollectionFactoryInterface::class);

        return $factory;
    }
}
