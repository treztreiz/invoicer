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
use App\Application\Dto\Customer\Output\CustomerOutput;
use App\Application\Dto\User\Input\PasswordInput;
use App\Application\Dto\User\Output\UserOutput;
use App\Infrastructure\ApiPlatform\State\Customer\CustomerStatusStateProcessor;
use App\Infrastructure\ApiPlatform\State\User\UpdatePasswordProcessor;
use Symfony\Component\HttpFoundation\Response;

final class ResourceRegistry
{
    /** @var array<class-string, list<ApiResource>> */
    private array $resources;

    /**
     * @param array<class-string, ApiResource|list<ApiResource>>|null $resources
     */
    public function __construct(
        ?array $resources = null,
    ) {
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
                            processor: UpdatePasswordProcessor::class,
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
                        new Get(uriTemplate: '/{customerId}', name: 'api_customers_get'),
                        new Post(name: 'api_customers_post'),
                        new Put(uriTemplate: '/{customerId}', read: false, name: 'api_customers_put'),
                        new Post(
                            uriTemplate: '/{customerId}/archive',
                            status: Response::HTTP_OK,
                            input: false,
                            read: false,
                            deserialize: false,
                            name: 'api_customers_archive',
                            processor: CustomerStatusStateProcessor::class,
                        ),
                        new Post(
                            uriTemplate: '/{customerId}/restore',
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
            //            QuoteOutput::class => [
            //                new ApiResource(
            //                    shortName: 'Quote',
            //                    operations: [
            //                        new GetCollection(
            //                            name: 'api_quotes_get_collection',
            //                            stateOptions: new Options(Quote::class, 'api_platform.doctrine.orm.links_handler'),
            //                            parameters: [
            //                                'createdAt' => new QueryParameter(key: 'createdAt', filter: 'app.api_platform.quote_date_filter', property: 'createdAt'),
            //                                'status' => new QueryParameter(key: 'status', filter: 'app.api_platform.quote_status_filter', property: 'status'),
            //                            ],
            // //                            parameters: [
            // //                                'status' => EnumParameter::create(
            // //                                    key: 'status',
            // //                                    description: 'Filter by one or multiple quote statuses.',
            // //                                    enumFqcn: QuoteStatus::class,
            // //                                    enumValues: fn() => QuoteStatus::statuses()
            // //                                ),
            // //                                'customerId' => UuidParameter::create(
            // //                                    key: 'customerId',
            // //                                    description: 'Filter by customer id.',
            // //                                ),
            // //                                'createdAfter' => DateParameter::create(
            // //                                    key: 'createdAfter',
            // //                                    description: 'Filter by quote created after date.',
            // //                                ),
            // //                                'createdBefore' => DateParameter::create(
            // //                                    key: 'createdBefore',
            // //                                    description: 'Filter by quote created before date.',
            // //                                ),
            // //                            ]
            //                        ),
            //                        new Get(
            //                            uriTemplate: '/{quoteId}',
            // //                            uriVariables: [
            // //                                'quoteId' => new Link(
            // //                                    parameterName: 'quoteId',
            // ////                                    toProperty: 'id',
            // ////                                    toClass: Quote::class,
            // //                                ),
            // //                            ],
            //                            name: 'api_quotes_get',
            // //                            provider: ItemProvider::class,
            //                            stateOptions: new Options(Quote::class, 'api_platform.doctrine.orm.links_handler'),
            //                        ),
            // //                        new Post(name: 'api_quotes_post'),
            // //                        new Put(
            // //                            uriTemplate: '/{quoteId}',
            // //                            read: false,
            // //                            name: 'api_quotes_put',
            // //                            processor: UpdateQuoteProcessor::class,
            // //                        ),
            // //                        new Post(
            // //                            uriTemplate: '/{quoteId}/transition',
            // //                            status: Response::HTTP_OK,
            // //                            denormalizationContext: ['groups' => ['quote:transition']],
            // //                            input: ['class' => QuoteTransitionInput::class],
            // //                            read: false,
            // //                            name: 'api_quotes_transition',
            // //                            processor: TransitionQuoteProcessor::class,
            // //                        ),
            //                    ],
            //                    routePrefix: '/quotes',
            //                ),
            //            ],
            //            InvoiceOutput::class => [
            //                new ApiResource(
            //                    shortName: 'Invoice',
            //                    operations: [
            //                        new GetCollection(name: 'api_invoices_get_collection'),
            //                        new Get(uriTemplate: '/{invoiceId}', name: 'api_invoices_get'),
            //                        new Post(name: 'api_invoices_post'),
            //                        new Put(
            //                            uriTemplate: '/{invoiceId}',
            //                            read: false,
            //                            name: 'api_invoices_put',
            //                            processor: UpdateInvoiceProcessor::class,
            //                        ),
            //                        new Post(
            //                            uriTemplate: '/{invoiceId}/transition',
            //                            status: Response::HTTP_OK,
            //                            denormalizationContext: ['groups' => ['invoice:transition']],
            //                            input: ['class' => TransitionInvoiceInput::class],
            //                            read: false,
            //                            name: 'api_invoices_transition',
            //                            processor: TransitionInvoiceProcessor::class,
            //                        ),
            //                        new Post(
            //                            uriTemplate: '/{invoiceId}/recurrence',
            //                            status: Response::HTTP_OK,
            //                            denormalizationContext: ['groups' => ['invoice:recurrence']],
            //                            input: ['class' => InvoiceRecurrenceInput::class],
            //                            read: false,
            //                            name: 'api_invoices_recurrence_post',
            //                            processor: AttachInvoiceRecurrenceProcessor::class,
            //                        ),
            //                        new Put(
            //                            uriTemplate: '/{invoiceId}/recurrence',
            //                            status: Response::HTTP_OK,
            //                            denormalizationContext: ['groups' => ['invoice:recurrence']],
            //                            input: ['class' => InvoiceRecurrenceInput::class],
            //                            read: false,
            //                            name: 'api_invoices_recurrence_put',
            //                            processor: AttachInvoiceRecurrenceProcessor::class,
            //                        ),
            //                        new Delete(
            //                            uriTemplate: '/{invoiceId}/recurrence',
            //                            status: Response::HTTP_OK,
            //                            input: false,
            //                            read: false,
            //                            deserialize: false,
            //                            name: 'api_invoices_recurrence_delete',
            //                            processor: DetachInvoiceRecurrenceProcessor::class,
            //                        ),
            //                        new Post(
            //                            uriTemplate: '/{invoiceId}/installment-plan',
            //                            status: Response::HTTP_OK,
            //                            denormalizationContext: ['groups' => ['invoice:installment']],
            //                            input: ['class' => InstallmentPlanInput::class],
            //                            read: false,
            //                            name: 'api_invoices_installment_plan_post',
            //                            processor: AttachInvoiceInstallmentPlanProcessor::class,
            //                        ),
            //                        new Put(
            //                            uriTemplate: '/{invoiceId}/installment-plan',
            //                            status: Response::HTTP_OK,
            //                            denormalizationContext: ['groups' => ['invoice:installment']],
            //                            input: ['class' => InstallmentPlanInput::class],
            //                            read: false,
            //                            name: 'api_invoices_installment_plan_put',
            //                            processor: AttachInvoiceInstallmentPlanProcessor::class,
            //                        ),
            //                        new Delete(
            //                            uriTemplate: '/{invoiceId}/installment-plan',
            //                            status: Response::HTTP_OK,
            //                            input: false,
            //                            read: false,
            //                            deserialize: false,
            //                            name: 'api_invoices_installment_plan_delete',
            //                            processor: DetachInstallmentPlanProcessor::class,
            //                        ),
            //                    ],
            //                    routePrefix: '/invoices',
            //                ),
            //            ],
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
