<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Metadata;

use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

// #[AsDecorator(decorates: 'api_platform.metadata.resource.name_collection_factory')]
final readonly class ResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    public function __construct(
        #[AutowireDecorated]
        private ResourceNameCollectionFactoryInterface $decorated,
        private ResourceRegistry $registry,
    ) {
    }

    public function create(): ResourceNameCollection
    {
        $collection = $this->decorated->create();

        $existing = iterator_to_array($collection);
        $additional = array_diff($this->registry->resourceClasses(), $existing);

        return empty($additional)
            ? $collection
            : new ResourceNameCollection([...$existing, ...$additional]);
    }
}
