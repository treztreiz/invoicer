<?php

/**
 * @noinspection PhpSameParameterValueInspection
 * @noinspection PhpUnhandledExceptionInspection
 */

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\ApiPlatform\Metadata;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use App\Infrastructure\ApiPlatform\Metadata\ResourceRegistry;
use App\Tests\ConfigurableKernel;
use App\Tests\ConfigurableKernelTestCase;
use App\Tests\Integration\Infrastructure\ApiPlatform\Metadata\Fixtures\State\Dummy\DummyStateProcessor;
use App\Tests\Integration\Infrastructure\ApiPlatform\Metadata\Fixtures\State\Dummy\DummyStateProvider;
use App\Tests\Integration\Infrastructure\ApiPlatform\Metadata\Fixtures\UseCase\Dummy\Command\DummyCommand;
use App\Tests\Integration\Infrastructure\ApiPlatform\Metadata\Fixtures\UseCase\Dummy\Result\DummyResult;

/**
 * @testType integration
 */
final class ResourceMetadataCollectionFactoryTest extends ConfigurableKernelTestCase
{
    protected static function setKernelConfiguration(ConfigurableKernel $kernel): iterable
    {
        $baseNamespace = 'App\Tests\Integration\Infrastructure\ApiPlatform\Metadata\Fixtures';

        yield 'parameters' => [
            'app.api_platform.metadata.output_suffix' => 'Result',
            'app.api_platform.metadata.output_class_template' => $baseNamespace.'\UseCase\%%%%s\Result\%sResult',
            'app.api_platform.metadata.input_class_template' => $baseNamespace.'\UseCase\%%%%s\Command\%sCommand',
            'app.api_platform.metadata.provider_class_template' => $baseNamespace.'\State\%%%%s\%sStateProvider',
            'app.api_platform.metadata.processor_class_template' => $baseNamespace.'\State\%%%%s\%sStateProcessor',
            'app.api_platform.metadata.controller' => 'dummy_controller',
        ];
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $stubRegistry = new ResourceRegistry([
            DummyResult::class => [
                new ApiResource(
                    uriTemplate: '/dummy',
                    operations: [
                        new Get(name: 'api_dummy_get'),
                        new Post(name: 'api_dummy_create'),
                        new Put(name: 'api_dummy_update'),
                    ],
                    uriVariables: []
                ),
                new ApiResource(
                    uriTemplate: '/dummy/secondary',
                    operations: [
                        new Get(name: 'api_dummy_secondary_get'),
                    ],
                ),
            ],
        ]);

        self::getContainer()->set(ResourceRegistry::class, $stubRegistry);
    }

    public function test_registry_backed_resources_are_discovered_and_wired(): void
    {
        // Ensure resources name are registered
        /** @var ResourceNameCollectionFactoryInterface $nameCollectionFactory */
        $nameCollectionFactory = self::getContainer()->get(ResourceNameCollectionFactoryInterface::class);
        $resourceNames = iterator_to_array($nameCollectionFactory->create());
        static::assertContains(DummyResult::class, $resourceNames);

        // Ensure resources metadata are registered
        /** @var ResourceMetadataCollectionFactoryInterface $metadataCollectionFactory */
        $metadataCollectionFactory = self::getContainer()->get(ResourceMetadataCollectionFactoryInterface::class);
        $metadataCollection = $metadataCollectionFactory->create(DummyResult::class);
        static::assertCount(2, $metadataCollection);

        // PRIMARY RESOURCE
        /** @var ApiResource $primaryResource */
        $primaryResource = $metadataCollection[0];
        static::assertResourceIsWired($primaryResource, '/dummy', []);
        // Ensure primary resource operations are wired
        $primaryOperations = iterator_to_array($primaryResource->getOperations());
        static::assertCount(3, $primaryOperations);
        static::assertReadOperationIsWired($primaryResource, $primaryOperations, 'api_dummy_get');
        static::assertWriteOperationIsWired($primaryResource, $primaryOperations, 'api_dummy_create');
        static::assertWriteOperationIsWired($primaryResource, $primaryOperations, 'api_dummy_update');

        // SECONDARY RESOURCE
        /** @var ApiResource $secondaryResource */
        $secondaryResource = $metadataCollection[1];
        static::assertResourceIsWired($secondaryResource, '/dummy/secondary', null);
        // Ensure secondary operation is wired
        $secondaryOperations = iterator_to_array($secondaryResource->getOperations());
        static::assertCount(1, $secondaryOperations);
        static::assertReadOperationIsWired($secondaryResource, $secondaryOperations, 'api_dummy_secondary_get');
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private static function assertResourceIsWired(ApiResource $resource, string $uriTemplate, ?array $uriVariables): void
    {
        static::assertSame(DummyResult::class, $resource->getClass());
        static::assertSame('DummyResult', $resource->getShortName());
        static::assertSame($uriTemplate, $resource->getUriTemplate());
        static::assertSame($uriVariables, $resource->getUriVariables());
    }

    private static function assertReadOperationIsWired(ApiResource $resource, array $operations, string $name): void
    {
        static::assertArrayHasKey($name, $operations);

        /** @var HttpOperation $operation */
        $operation = $operations[$name];

        static::assertSame(DummyResult::class, $operation->getClass());
        // Ensure controller is autoconfigured
        static::assertSame('dummy_controller', $operation->getController());
        // Ensure states are autoconfigured
        static::assertSame(DummyStateProvider::class, $operation->getProvider());
        static::assertNull($operation->getProcessor());
        // Ensure serialization is autoconfigured
        static::assertSame(['dummy:read'], $operation->getNormalizationContext()['groups'] ?? null);
        static::assertNull($operation->getDenormalizationContext());
        // Ensure uri is autoconfigured
        static::assertSame($resource->getUriTemplate(), $operation->getUriTemplate());
        static::assertSame($resource->getUriVariables(), $operation->getUriVariables());
    }

    private static function assertWriteOperationIsWired(ApiResource $resource, array $operations, string $name): void
    {
        static::assertArrayHasKey($name, $operations);

        /** @var HttpOperation $operation */
        $operation = $operations[$name];

        static::assertSame(DummyResult::class, $operation->getClass());
        // Ensure controller is autoconfigured
        static::assertSame('dummy_controller', $operation->getController());
        // Ensure states are autoconfigured
        static::assertSame(DummyStateProcessor::class, $operation->getProcessor());
        static::assertNull($operation->getProvider());
        // Ensure input is autoconfigured
        static::assertSame(DummyCommand::class, $operation->getInput()['class'] ?? null);
        static::assertNotEmpty($operation->getInputFormats());
        // Ensure uri is autoconfigured
        static::assertSame($resource->getUriTemplate(), $operation->getUriTemplate());
        static::assertSame($resource->getUriVariables(), $operation->getUriVariables());
    }
}
