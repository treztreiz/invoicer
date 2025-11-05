<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Metadata;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use App\Application\UseCase\Me\Result\MeResult;

final class ResourceRegistry
{
    /** @var array<class-string, ApiResource> */
    private array $resources;

    /**
     * @param array<class-string, ApiResource>|null $resources
     */
    public function __construct(?array $resources = null)
    {
        $this->resources = $resources ?? [
            MeResult::class => new ApiResource(
                uriTemplate: '/me',
                operations: [
                    new Get(
                        normalizationContext: ['groups' => ['me:read']],
                        name: 'api_me_get'
                    ),
                    new Put(
                        normalizationContext: ['groups' => ['me:read']],
                        denormalizationContext: ['groups' => ['me:write']],
                        name: 'api_me_update'
                    ),
                ],
                uriVariables: []
            ),
        ];
    }

    public function find(string $resourceClasse): ?ApiResource
    {
        return $this->resources[$resourceClasse] ?? null;
    }

    /**
     * @return list<class-string>
     */
    public function resourceClasses(): array
    {
        return array_keys($this->resources);
    }
}
