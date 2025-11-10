<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Metadata;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\Response as OpenApiResponse;
use App\Application\UseCase\Customer\Output\CustomerOutput;
use App\Application\UseCase\Invoice\Input\InvoiceActionInput;
use App\Application\UseCase\Invoice\Input\InvoiceInstallmentPlanInput;
use App\Application\UseCase\Invoice\Input\InvoiceRecurrenceInput;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Quote\Input\QuoteActionInput;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Application\UseCase\User\Input\PasswordInput;
use App\Application\UseCase\User\Output\UserOutput;
use App\Infrastructure\ApiPlatform\State\Customer\CustomerStatusStateProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\InvoiceActionStateProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\InvoiceInstallmentPlanDeleteStateProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\InvoiceInstallmentPlanStateProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\InvoiceRecurrenceDeleteStateProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\InvoiceRecurrenceStateProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\InvoiceStateProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\InvoiceStateProvider;
use App\Infrastructure\ApiPlatform\State\Quote\QuoteActionStateProcessor;
use App\Infrastructure\ApiPlatform\State\Quote\QuoteStateProcessor;
use App\Infrastructure\ApiPlatform\State\Quote\QuoteStateProvider;
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
            UserOutput::class => [
                new ApiResource(
                    shortName: 'Me',
                    operations: [
                        new Get(name: 'api_me_get'),
                        new Put(name: 'api_me_update'),
                        new Post(
                            uriTemplate: '/password',
                            status: Response::HTTP_NO_CONTENT,
                            openapi: new OpenApiOperation(
                                responses: [
                                    Response::HTTP_NO_CONTENT => new OpenApiResponse(description: 'Password updated; client must re-authenticate with the new secret.'),
                                ],
                                summary: 'Rotate current user password',
                                description: 'Hashes the new password, persists it, and invalidates active sessions.',
                            ),
                            denormalizationContext: ['groups' => ['user:password:write']],
                            input: ['class' => PasswordInput::class],
                            output: false,
                            read: false,
                            name: 'api_me_change_password',
                            processor: PasswordStateProcessor::class,
                        ),
                    ],
                    routePrefix: '/me',
                ),
            ],
            CustomerOutput::class => [
                new ApiResource(
                    shortName: 'Customer',
                    operations: [
                        new GetCollection(name: 'api_customers_get_collection'),
                        new Get(uriTemplate: '/{id}', name: 'api_customers_get'),
                        new Post(name: 'api_customers_post'),
                        new Put(uriTemplate: '/{id}', read: false, name: 'api_customers_put'),
                        new Post(
                            uriTemplate: '/{id}/archive',
                            status: Response::HTTP_OK,
                            input: false,
                            read: false,
                            deserialize: false,
                            name: 'api_customers_archive',
                            processor: CustomerStatusStateProcessor::class,
                        ),
                        new Post(
                            uriTemplate: '/{id}/restore',
                            status: Response::HTTP_OK,
                            input: false,
                            read: false,
                            deserialize: false,
                            name: 'api_customers_restore',
                            processor: CustomerStatusStateProcessor::class,
                        ),
                    ],
                    routePrefix: '/customers',
                ),
            ],
            QuoteOutput::class => [
                new ApiResource(
                    shortName: 'Quote',
                    operations: [
                        new GetCollection(
                            name: 'api_quotes_get_collection',
                            provider: QuoteStateProvider::class,
                        ),
                        new Get(
                            uriTemplate: '/{id}',
                            name: 'api_quotes_get',
                            provider: QuoteStateProvider::class,
                        ),
                        new Post(
                            name: 'api_quotes_post',
                            processor: QuoteStateProcessor::class,
                        ),
                        new Post(
                            uriTemplate: '/{id}/actions',
                            status: Response::HTTP_OK,
                            denormalizationContext: ['groups' => ['quote:action']],
                            input: ['class' => QuoteActionInput::class],
                            read: false,
                            name: 'api_quotes_action',
                            processor: QuoteActionStateProcessor::class,
                        ),
                    ],
                    routePrefix: '/quotes',
                ),
            ],
            InvoiceOutput::class => [
                new ApiResource(
                    shortName: 'Invoice',
                    operations: [
                        new GetCollection(
                            name: 'api_invoices_get_collection',
                            provider: InvoiceStateProvider::class,
                        ),
                        new Get(
                            uriTemplate: '/{id}',
                            name: 'api_invoices_get',
                            provider: InvoiceStateProvider::class,
                        ),
                        new Post(
                            name: 'api_invoices_post',
                            processor: InvoiceStateProcessor::class,
                        ),
                        new Post(
                            uriTemplate: '/{id}/actions',
                            status: Response::HTTP_OK,
                            denormalizationContext: ['groups' => ['invoice:action']],
                            input: ['class' => InvoiceActionInput::class],
                            read: false,
                            name: 'api_invoices_action',
                            processor: InvoiceActionStateProcessor::class,
                        ),
                        new Post(
                            uriTemplate: '/{id}/recurrence',
                            status: Response::HTTP_OK,
                            denormalizationContext: ['groups' => ['invoice:recurrence']],
                            input: ['class' => InvoiceRecurrenceInput::class],
                            read: false,
                            name: 'api_invoices_recurrence_post',
                            processor: InvoiceRecurrenceStateProcessor::class,
                        ),
                        new Put(
                            uriTemplate: '/{id}/recurrence',
                            status: Response::HTTP_OK,
                            denormalizationContext: ['groups' => ['invoice:recurrence']],
                            input: ['class' => InvoiceRecurrenceInput::class],
                            read: false,
                            name: 'api_invoices_recurrence_put',
                            processor: InvoiceRecurrenceStateProcessor::class,
                        ),
                        new Delete(
                            uriTemplate: '/{id}/recurrence',
                            status: Response::HTTP_OK,
                            input: false,
                            read: false,
                            deserialize: false,
                            name: 'api_invoices_recurrence_delete',
                            processor: InvoiceRecurrenceDeleteStateProcessor::class,
                        ),
                        new Post(
                            uriTemplate: '/{id}/installment-plan',
                            status: Response::HTTP_OK,
                            denormalizationContext: ['groups' => ['invoice:installment']],
                            input: ['class' => InvoiceInstallmentPlanInput::class],
                            read: false,
                            name: 'api_invoices_installment_plan_post',
                            processor: InvoiceInstallmentPlanStateProcessor::class,
                        ),
                        new Put(
                            uriTemplate: '/{id}/installment-plan',
                            status: Response::HTTP_OK,
                            denormalizationContext: ['groups' => ['invoice:installment']],
                            input: ['class' => InvoiceInstallmentPlanInput::class],
                            read: false,
                            name: 'api_invoices_installment_plan_put',
                            processor: InvoiceInstallmentPlanStateProcessor::class,
                        ),
                        new Delete(
                            uriTemplate: '/{id}/installment-plan',
                            status: Response::HTTP_OK,
                            input: false,
                            read: false,
                            deserialize: false,
                            name: 'api_invoices_installment_plan_delete',
                            processor: InvoiceInstallmentPlanDeleteStateProcessor::class,
                        ),
                    ],
                    routePrefix: '/invoices',
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
