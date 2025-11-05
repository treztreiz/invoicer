<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Metadata;

use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

#[AsDecorator(decorates: 'api_platform.metadata.resource.name_collection_factory')]
final readonly class ResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    public function __construct(
        #[AutowireDecorated]
        private ResourceNameCollectionFactoryInterface $decorated,
        private ResourceRegistry $registry,
    ) {
    }

    public function create(?string $resourceClass = null): ResourceNameCollection
    {
        $resourceNameCollection = $this->decorated->create($resourceClass);

        if (null !== $resourceClass) {
            return $resourceNameCollection;
        }

        $existing = iterator_to_array($resourceNameCollection);
        $additional = array_diff($this->registry->resourceClasses(), $existing);

        if (empty($additional)) {
            return $resourceNameCollection;
        }

        return new ResourceNameCollection([...$existing, ...$additional]);
    }
}
