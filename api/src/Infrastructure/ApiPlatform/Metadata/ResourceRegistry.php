<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Metadata;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\Response as OpenApiResponse;
use App\Application\UseCase\Customer\Output\CustomerOutput;
use App\Application\UseCase\User\Input\PasswordInput;
use App\Application\UseCase\User\Output\UserOutput;
use App\Infrastructure\ApiPlatform\State\User\PasswordStateProcessor;
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
            UserOutput::class => new ApiResource(
                shortName: 'Me',
                operations: [
                    new Get(name: 'api_me_get'),
                    new Put(name: 'api_me_update'),
                ],
                routePrefix: '/me',
            ),
            PasswordInput::class => new ApiResource(
                shortName: 'Password',
                operations: [
                    new Post(
                        status: Response::HTTP_NO_CONTENT,
                        openapi: new OpenApiOperation(
                            responses: [
                                Response::HTTP_NO_CONTENT => new OpenApiResponse(description: 'Password updated; client must re-authenticate with the new secret.'),
                            ],
                            summary: 'Rotate current user password',
                            description: 'Hashes the new password, persists it, and invalidates active sessions.',
                        ),
                        denormalizationContext: ['groups' => ['user:password:write']],
                        output: false,
                        read: false,
                        name: 'api_me_change_password',
                        processor: PasswordStateProcessor::class,
                    ),
                ],
                routePrefix: '/me/password',
            ),
            CustomerOutput::class => [
                new ApiResource(
                    shortName: 'Customer',
                    operations: [
                        new GetCollection(name: 'api_customers_get_collection'),
                        new Get(uriTemplate: '/{id}', name: 'api_customers_get'),
                        new Post(status: Response::HTTP_CREATED, name: 'api_customers_post'),
                        new Put(uriTemplate: '/{id}', read: false, name: 'api_customers_put'),
                    ],
                    routePrefix: '/customers',
                ),
            ],
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
