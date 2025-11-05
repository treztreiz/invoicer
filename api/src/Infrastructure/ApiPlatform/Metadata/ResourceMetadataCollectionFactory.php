<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Metadata;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

#[AsDecorator(decorates: 'api_platform.metadata.resource.metadata_collection_factory')]
final class ResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    /** @var array{useCase: string, baseName: string}|null */
    private ?array $useCaseTokens = null;

    public function __construct(
        #[AutowireDecorated]
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
        private readonly ResourceRegistry $registry,
        #[Autowire(param: 'app.api_platform.metadata.result_class_template')]
        private readonly string $resultClassTemplate,
        #[Autowire(param: 'app.api_platform.metadata.command_class_template')]
        private readonly string $commandClassTemplate,
        #[Autowire(param: 'app.api_platform.metadata.provider_class_template')]
        private readonly string $providerClassTemplate,
        #[Autowire(param: 'app.api_platform.metadata.processor_class_template')]
        private readonly string $processorClassTemplate,
        #[Autowire(param: 'app.api_platform.metadata.controller')]
        private readonly string $controller,
    ) {
    }

    /** @param class-string $resourceClass
     * @throws ResourceClassNotFoundException
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        // Merge Api Platform's metadata with our registry defaults (if any).
        $resourceMetadataCollection = $this->seedCollection($resourceClass);

        // Only DTOs following the "UseCase\Result\*Result" convention get extra wiring.
        $this->useCaseTokens = $this->extractUseCaseTokens($resourceClass);
        if (null === $this->useCaseTokens) {
            return $resourceMetadataCollection;
        }

        return $this->configureCollection($resourceMetadataCollection, $resourceClass);
    }

    /**
     * @return array{useCase: string, baseName: string}|null [useCase, baseName]
     */
    private function extractUseCaseTokens(string $resourceClass): ?array
    {
        if (!str_ends_with($resourceClass, 'Result')) {
            return null;
        }

        $parts = explode('\\', $resourceClass);
        $resultSegmentIndex = array_search('Result', $parts, true);
        if (false === $resultSegmentIndex || 0 === $resultSegmentIndex) {
            return null;
        }

        $useCase = $parts[$resultSegmentIndex - 1] ?? null;
        if (null === $useCase) {
            return null;
        }

        $shortClass = $parts[array_key_last($parts)];
        $baseName = substr($shortClass, 0, -strlen('Result'));

        if ('' === $baseName) {
            return null;
        }

        $expectedClass = sprintf($this->resultClassTemplate, $useCase, $baseName);

        if ($resourceClass !== $expectedClass) {
            return null;
        }

        return [
            'useCase' => $useCase,
            'baseName' => $baseName,
        ];
    }

    /**
     * @throws ResourceClassNotFoundException
     */
    private function seedCollection(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        // Attributes/config already described the resource → nothing to seed from the registry.
        if (0 !== count($resourceMetadataCollection)) {
            return $resourceMetadataCollection;
        }

        $resource = $this->registry->find($resourceClass);
        if (null === $resource) {
            return $resourceMetadataCollection;
        }

        // Registry entry provides a first ApiResource definition for this DTO.
        return new ResourceMetadataCollection($resourceClass, [$resource]);
    }

    private function configureCollection(ResourceMetadataCollection $collection, string $resourceClass): ResourceMetadataCollection
    {
        foreach ($collection as $index => $resource) {
            if (!$resource instanceof ApiResource) {
                continue;
            }

            $collection[$index] = $this->configureResource($resource, $resourceClass);
        }

        return $collection;
    }

    // API RESOURCE METADATA ///////////////////////////////////////////////////////////////////////////////////////////

    private function configureResource(ApiResource $resource, string $resourceClass): ApiResource
    {
        $resource = $this->applyResourceDefaults($resource, $resourceClass);

        $operations = $resource->getOperations();

        if (null === $operations) {
            return $resource;
        }

        $updatedOperations = [];

        foreach ($operations as $operationName => $operation) {
            if (!$operation instanceof HttpOperation) {
                $updatedOperations[$operationName] = $operation;
                continue;
            }

            $updatedOperations[$operationName] = $this->configureOperation($operation, $resource);
        }

        return $resource->withOperations(new Operations($updatedOperations));
    }

    private function applyResourceDefaults(ApiResource $resource, string $resourceClass): ApiResource
    {
        if (null === $resource->getClass()) {
            $resource = $resource->withClass($resourceClass);
        }

        if (null === $resource->getShortName()) {
            $guessedShortName = $this->guessShortName($resource->getClass());
            $resource = $resource->withShortName($guessedShortName);
        }

        return $resource;
    }

    private function guessShortName(string $resourceClass): string
    {
        if (false === $pos = strrpos($resourceClass, '\\')) {
            return $resourceClass;
        }

        return substr($resourceClass, $pos + 1);
    }

    // OPERATIONS METADATA /////////////////////////////////////////////////////////////////////////////////////////////

    private function configureOperation(HttpOperation $operation, ApiResource $resource): HttpOperation
    {
        $operation = $this->applyUriDefaults($operation, $resource);
        $operation = $this->applyControllerDefaults($operation);
        $operation = $this->applyUseCaseDefaults($operation);

        return $this->applyPresentationDefaults($operation, $resource);
    }

    private function applyUriDefaults(HttpOperation $operation, ApiResource $resource): HttpOperation
    {
        $resourceUriTemplate = $resource->getUriTemplate();
        $resourceUriVariables = $resource->getUriVariables();

        if (null === $operation->getUriTemplate() && null !== $resourceUriTemplate) {
            $operation = $operation->withUriTemplate($resourceUriTemplate);
        }

        if (null === $operation->getUriVariables() && null !== $resourceUriVariables) {
            $operation = $operation->withUriVariables($resourceUriVariables);
        }

        return $operation;
    }

    private function applyControllerDefaults(HttpOperation $operation): HttpOperation
    {
        if (null === $operation->getController()) {
            $operation = $operation->withController($this->controller);
        }

        return $operation;
    }

    private function applyUseCaseDefaults(HttpOperation $operation): HttpOperation
    {
        $method = strtoupper($operation->getMethod());

        if ('GET' === $method) {
            $providerClass = $this->generateUseCaseClass($this->providerClassTemplate);

            if (null === $operation->getProvider() && class_exists($providerClass)) {
                $operation = $operation->withProvider($providerClass);
            }

            return $operation;
        }

        $processorClass = $this->generateUseCaseClass($this->processorClassTemplate);

        if (null === $operation->getProcessor() && class_exists($processorClass)) {
            $operation = $operation->withProcessor($processorClass);
        }

        $commandClass = $this->generateUseCaseClass($this->commandClassTemplate);

        if (null === $operation->getInput() && class_exists($commandClass)) {
            $operation = $operation->withInput($commandClass);
        }

        return $operation;
    }

    private function applyPresentationDefaults(HttpOperation $operation, ApiResource $resource): HttpOperation
    {
        $resourceClass = $resource->getClass() ?? throw new \LogicException('Resource class must be resolved before configuring presentation defaults.');
        $resourceShortName = $resource->getShortName() ?? throw new \LogicException('Resource short name must be resolved before configuring presentation defaults.');

        if (null === $operation->getShortName()) {
            $operation = $operation->withShortName($resourceShortName);
        }

        if (null === $operation->getClass()) {
            $operation = $operation->withClass($resourceClass);
        }

        return $operation;
    }

    /** @return class-string */
    private function generateUseCaseClass(string $template): string
    {
        if (null === $this->useCaseTokens) {
            throw new \LogicException('Use case tokens must be resolved before generating use case class names.');
        }

        /** @var class-string $class */
        $class = sprintf($template, $this->useCaseTokens['useCase'], $this->useCaseTokens['baseName']);

        return $class;
    }
}
