<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Metadata;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

#[AsDecorator(decorates: 'api_platform.metadata.resource.metadata_collection_factory')]
final readonly class ResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    /**
     * @param array<string, list<string>> $defaultFormats
     * @param array<string, list<string>> $defaultPatchFormats
     */
    public function __construct(
        #[AutowireDecorated]
        private ResourceMetadataCollectionFactoryInterface $decorated,
        private ResourceRegistry $registry,
        #[Autowire(param: 'app.api_platform.metadata.output_suffix')]
        private string $outputSuffix,
        #[Autowire(param: 'app.api_platform.metadata.output_class_template')]
        private string $outputClassTemplate,
        #[Autowire(param: 'app.api_platform.metadata.input_class_template')]
        private string $inputClassTemplate,
        #[Autowire(param: 'app.api_platform.metadata.provider_class_template')]
        private string $providerClassTemplate,
        #[Autowire(param: 'app.api_platform.metadata.processor_class_template')]
        private string $processorClassTemplate,
        #[Autowire(param: 'app.api_platform.metadata.controller')]
        private string $controller,
        #[Autowire(param: 'api_platform.formats')]
        private array $defaultFormats,
        #[Autowire(param: 'api_platform.patch_formats')]
        private array $defaultPatchFormats,
    ) {
    }

    /**
     * @param class-string $resourceClass
     *
     * @throws ResourceClassNotFoundException
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        return $this->configureCollection(
            $this->seedCollection($resourceClass),
            $resourceClass
        );
    }

    /**
     * @throws ResourceClassNotFoundException
     */
    private function seedCollection(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);
        $registryResources = $this->registry->resourcesFor($resourceClass);

        if ([] === $registryResources) {
            return $resourceMetadataCollection;
        }

        foreach ($registryResources as $resource) {
            $resourceMetadataCollection[] = $resource;
        }

        return $resourceMetadataCollection;
    }

    /**
     * @param class-string $resourceClass
     */
    private function configureCollection(ResourceMetadataCollection $collection, string $resourceClass): ResourceMetadataCollection
    {
        foreach ($collection as $index => $resource) {
            /* @phpstan-ignore-next-line defensive runtime guard */
            if (!$resource instanceof ApiResource) {
                continue;
            }

            $autoconfigure = $resource->getExtraProperties()['api.autoconfigure'] ?? false;
            if (false === $autoconfigure) {
                continue;
            }

            $collection[$index] = $this->configureResource($resource, $resourceClass);
        }

        return $collection;
    }

    // API RESOURCE METADATA ///////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param class-string $resourceClass
     */
    private function configureResource(ApiResource $resource, string $resourceClass): ApiResource
    {
        $resource = $this->applyResourceDefaults($resource, $resourceClass);
        $resource = $this->applyClassesTokens($resource);

        $operations = $resource->getOperations();

        if (null === $operations) {
            return $resource;
        }

        $updatedOperations = [];

        foreach ($operations as $operationName => $operation) {
            $updatedOperations[$operationName] = $this->configureOperation($operation, $resource);
        }

        return $resource->withOperations(new Operations($updatedOperations));
    }

    /**
     * @param class-string $resourceClass
     */
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

    private function applyClassesTokens(ApiResource $resource): ApiResource
    {
        $parts = explode('\\', $resource->getClass());

        // Skip if last segment doesn't contain expected suffixe
        $outputSegmentIndex = array_search($this->outputSuffix, $parts, true);
        if (false === $outputSegmentIndex || 0 === $outputSegmentIndex) {
            return $resource;
        }

        // Retrieve useCase and baseName placeholders
        $useCase = $parts[$outputSegmentIndex - 1] ?? null;

        $shortClass = $parts[array_key_last($parts)];
        $baseName = substr($shortClass, 0, -strlen($this->outputSuffix));

        // Skip if resource fqcn doesn't contain expected placeholders
        if (null === $useCase || '' === $baseName) {
            return $resource;
        }

        // Skip if resource fqcn doesn't match expected fqcn
        $expectedClass = sprintf($this->outputClassTemplate, $useCase, $baseName);
        if ($resource->getClass() !== $expectedClass) {
            return $resource;
        }

        return $resource->withExtraProperties([
            ...$resource->getExtraProperties(),
            'token.use_case' => $useCase,
            'token.base_name' => $baseName,
        ]);
    }

    /**
     * @param class-string $resourceClass
     */
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
        $operation = $this->applyFormatDefaults($operation);
        $operation = $this->applyControllerDefaults($operation);
        $operation = $this->applyClassesDefaults($operation, $resource);
        $operation = $this->applyNormalizationDefaults($operation, $resource);

        return $this->applyPresentationDefaults($operation, $resource);
    }

    private function applyUriDefaults(HttpOperation $operation, ApiResource $resource): HttpOperation
    {
        /** @var array<int|string, mixed>|null $resourceUriVariables */
        $resourceUriVariables = $resource->getUriVariables();
        $resourceRoutePrefix = $resource->getRoutePrefix();
        $resourceUriTemplate = $resource->getUriTemplate();

        if (null !== $operation->getUriTemplate() && null === $operation->getRoutePrefix() && null !== $resourceRoutePrefix) {
            $operation = $operation->withRoutePrefix($resourceRoutePrefix);
        }

        if (null === $operation->getUriTemplate()) {
            $operation = $operation->withUriTemplate($resourceUriTemplate ?? $resourceRoutePrefix ?? null);
        }

        if (null === $operation->getUriVariables() && null !== $resourceUriVariables) {
            $operation = $operation->withUriVariables($resourceUriVariables);
        }

        if (null === $operation->getUriVariables() && null !== $operationUriVariables = $this->guessUriVariables($operation)) {
            $operation = $operation->withUriVariables($operationUriVariables);
        }

        if (null === $operation->getFilters()) {
            $operation = $operation->withFilters($resource->getFilters() ?: []);
        }

        return $operation;
    }

    private function applyFormatDefaults(HttpOperation $operation): HttpOperation
    {
        $method = strtoupper($operation->getMethod() ?: 'GET');

        if ($this->isWriteOperation($method) && empty($operation->getInputFormats())) {
            $inputFormats = 'PATCH' === $method && !empty($this->defaultPatchFormats)
                ? $this->defaultPatchFormats
                : $this->defaultFormats;

            if (!empty($inputFormats)) {
                $operation = $operation->withInputFormats($inputFormats);
            }
        }

        if (empty($operation->getOutputFormats()) && !empty($this->defaultFormats)) {
            $operation = $operation->withOutputFormats($this->defaultFormats);
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

    private function applyClassesDefaults(HttpOperation $operation, ApiResource $resource): HttpOperation
    {
        $method = strtoupper($operation->getMethod() ?: 'GET');

        // Apply provider configuration
        if (null === $operation->getProvider()) {
            $provider = $resource->getProvider();
            if (null === $provider && 'GET' === $method) {
                $provider = $this->generateClassFromTokens($resource, $this->providerClassTemplate);
            }

            if (is_callable($provider) || (is_string($provider) && class_exists($provider))) {
                $operation = $operation->withProvider($provider);
            }
        }

        // GET operation doesn't need processor/input/output configuration
        if ('GET' === $method) {
            return $operation;
        }

        // Apply processor configuration
        if (null === $operation->getProcessor()) {
            $processor = $resource->getProcessor() ?: $this->generateClassFromTokens($resource, $this->processorClassTemplate);

            if (is_callable($processor) || (is_string($processor) && class_exists($processor))) {
                $operation = $operation->withProcessor($processor);
            }
        }

        // Apply Input configuration
        if (null === $operation->getInput()) {
            $input = $resource->getInput() ?: $this->generateClassFromTokens($resource, $this->inputClassTemplate);

            if (is_string($input) && class_exists($input)) {
                $operation = $operation->withInput(['class' => $input]);
            } elseif (is_callable($input)) {
                $operation = $operation->withInput($input);
            }
        }

        // Apply output configuration
        if (null === $operation->getOutput()) {
            $output = $resource->getOutput();

            if (is_string($output) && class_exists($output)) {
                $operation = $operation->withOutput(['class' => $output]);
            } elseif (is_callable($output)) {
                $operation = $operation->withOutput($output);
            }
        }

        return $operation;
    }

    private function applyNormalizationDefaults(HttpOperation $operation, ApiResource $resource): HttpOperation
    {
        $baseName = $resource->getExtraProperties()['token.base_name'] ?? null;
        if (!is_string($baseName)) {
            return $operation;
        }

        $baseGroup = strtolower($baseName);

        if (!($operation->getNormalizationContext()['groups'] ?? false)) {
            $operation = $operation->withNormalizationContext([
                ...$operation->getNormalizationContext() ?? [],
                'groups' => [$baseGroup.':read'],
            ]);
        }

        $method = strtoupper($operation->getMethod() ?: 'GET');

        if ($this->isWriteOperation($method) && !($operation->getDenormalizationContext()['groups'] ?? false)) {
            $operation = $operation->withDenormalizationContext([
                ...$operation->getDenormalizationContext() ?? [],
                'groups' => [$baseGroup.':write'],
            ]);
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

    /** @return array<string, Link>|null */
    private function guessUriVariables(HttpOperation $operation): ?array
    {
        $uriTemplate = $operation->getUriTemplate();

        if (null === $uriTemplate) {
            return null;
        }

        preg_match_all('/\{([^}]+)}/', $uriTemplate, $matches);

        /** @phpstan-ignore-next-line */
        $variables = $matches[1] ?? [];
        if ([] === $variables) {
            return null;
        }

        $links = [];
        foreach ($variables as $variable) {
            $links[$variable] = new Link(parameterName: $variable);
        }

        return $links;
    }

    /**
     * @return class-string|null
     */
    private function generateClassFromTokens(ApiResource $resource, string $template): ?string
    {
        $extra = $resource->getExtraProperties();

        if (!isset($extra['token.use_case']) || !isset($extra['token.base_name'])) {
            return null;
        }

        if (!is_string($extra['token.use_case']) || !is_string($extra['token.base_name'])) {
            throw new \InvalidArgumentException('"token.use_case" and "token.base_name" must be strings.');
        }

        /** @var class-string $class */
        $class = sprintf($template, $extra['token.use_case'], $extra['token.base_name']);

        return $class;
    }

    private function isWriteOperation(string $method): bool
    {
        return \in_array($method, ['POST', 'PUT', 'PATCH'], true);
    }
}
