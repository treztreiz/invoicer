<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Metadata;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\Response as OpenApiResponse;
use App\Application\UseCase\Me\Input\ChangePasswordInput;
use App\Application\UseCase\Me\Output\MeOutput;
use App\Infrastructure\ApiPlatform\State\Me\ChangePasswordStateProcessor;
use Symfony\Component\HttpFoundation\Response;

final class ResourceRegistry
{
    /** @var array<class-string, list<ApiResource>> */
    private array $resources;

    /**
     * @param array<class-string, ApiResource|list<ApiResource>>|null $resources
     */
    public function __construct(?array $resources = null)
    {
        $defaults = [
            MeOutput::class => new ApiResource(
                uriTemplate: '/me',
                shortName: 'Me',
                operations: [
                    new Get(name: 'api_me_get'),
                    new Put(name: 'api_me_update'),
                ],
                uriVariables: []
            ),
            ChangePasswordInput::class => new ApiResource(
                uriTemplate: '/me/password',
                shortName: 'ChangePassword',
                operations: [
                    new Post(
                        status: Response::HTTP_NO_CONTENT,
                        openapi: new OpenApiOperation(
                            responses: [
                                Response::HTTP_NO_CONTENT => new OpenApiResponse(
                                    description: 'Password updated; client must re-authenticate with the new secret.'
                                ),
                            ],
                            summary: 'Rotate current user password',
                            description: 'Hashes the new password, persists it, and invalidates active sessions.',
                        ),
                        denormalizationContext: ['groups' => ['me:password:write']],
                        output: false,
                        read: false,
                        name: 'api_me_change_password',
                        processor: ChangePasswordStateProcessor::class,
                    ),
                ],
                uriVariables: []
            ),
        ];

        $this->register($resources ?? $defaults);
    }

    /** @param array<class-string, ApiResource|list<ApiResource>|array<ApiResource>> $resources */
    private function register(array $resources): void
    {
        $this->resources = [];

        foreach ($resources as $resourceClass => $resource) {
            $resourceList = \is_array($resource) ? array_values($resource) : [$resource];

            $this->resources[$resourceClass] = array_map(
                static fn (ApiResource $resource) => $resource->withExtraProperties([
                    'api.autoconfigure' => true,
                    ...$resource->getExtraProperties(),
                ]),
                $resourceList
            );
        }
    }

    /**
     * @return list<ApiResource>
     */
    public function resourcesFor(string $resourceClass): array
    {
        return $this->resources[$resourceClass] ?? [];
    }

    /**
     * @return list<class-string>
     */
    public function resourceClasses(): array
    {
        return array_keys($this->resources);
    }
}
