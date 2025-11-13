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
use App\Application\UseCase\Invoice\Input\InvoiceInstallmentPlanInput;
use App\Application\UseCase\Invoice\Input\InvoiceRecurrenceInput;
use App\Application\UseCase\Invoice\Input\InvoiceTransitionInput;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Quote\Input\QuoteTransitionInput;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Application\UseCase\User\Input\PasswordInput;
use App\Application\UseCase\User\Output\UserOutput;
use App\Domain\Enum\QuoteStatus;
use App\Infrastructure\ApiPlatform\Metadata\Parameter\EnumParameter;
use App\Infrastructure\ApiPlatform\State\Customer\CustomerStatusStateProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\InvoiceInstallmentPlanDeleteStateProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\InvoiceInstallmentPlanStateProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\InvoiceRecurrenceDeleteStateProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\InvoiceRecurrenceStateProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\InvoiceTransitionStateProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\InvoiceUpdateStateProcessor;
use App\Infrastructure\ApiPlatform\State\Quote\QuoteTransitionStateProcessor;
use App\Infrastructure\ApiPlatform\State\Quote\QuoteUpdateStateProcessor;
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
            QuoteOutput::class => [
                new ApiResource(
                    shortName: 'Quote',
                    operations: [
                        new GetCollection(
                            name: 'api_quotes_get_collection',
                            parameters: [
                                'status' => EnumParameter::create(
                                    key: 'status',
                                    description: 'Filter by one or multiple quote statuses.',
                                    enumFqcn: QuoteStatus::class,
                                    enumValues: fn () => QuoteStatus::statuses()
                                ),
                            ]
                        ),
                        new Get(uriTemplate: '/{quoteId}', name: 'api_quotes_get'),
                        new Post(name: 'api_quotes_post'),
                        new Put(
                            uriTemplate: '/{quoteId}',
                            read: false,
                            name: 'api_quotes_put',
                            processor: QuoteUpdateStateProcessor::class,
                        ),
                        new Post(
                            uriTemplate: '/{quoteId}/transition',
                            status: Response::HTTP_OK,
                            denormalizationContext: ['groups' => ['quote:transition']],
                            input: ['class' => QuoteTransitionInput::class],
                            read: false,
                            name: 'api_quotes_transition',
                            processor: QuoteTransitionStateProcessor::class,
                        ),
                    ],
                    routePrefix: '/quotes',
                ),
            ],
            InvoiceOutput::class => [
                new ApiResource(
                    shortName: 'Invoice',
                    operations: [
                        new GetCollection(name: 'api_invoices_get_collection'),
                        new Get(uriTemplate: '/{invoiceId}', name: 'api_invoices_get'),
                        new Post(name: 'api_invoices_post'),
                        new Put(
                            uriTemplate: '/{invoiceId}',
                            read: false,
                            name: 'api_invoices_put',
                            processor: InvoiceUpdateStateProcessor::class,
                        ),
                        new Post(
                            uriTemplate: '/{invoiceId}/transition',
                            status: Response::HTTP_OK,
                            denormalizationContext: ['groups' => ['invoice:transition']],
                            input: ['class' => InvoiceTransitionInput::class],
                            read: false,
                            name: 'api_invoices_transition',
                            processor: InvoiceTransitionStateProcessor::class,
                        ),
                        new Post(
                            uriTemplate: '/{invoiceId}/recurrence',
                            status: Response::HTTP_OK,
                            denormalizationContext: ['groups' => ['invoice:recurrence']],
                            input: ['class' => InvoiceRecurrenceInput::class],
                            read: false,
                            name: 'api_invoices_recurrence_post',
                            processor: InvoiceRecurrenceStateProcessor::class,
                        ),
                        new Put(
                            uriTemplate: '/{invoiceId}/recurrence',
                            status: Response::HTTP_OK,
                            denormalizationContext: ['groups' => ['invoice:recurrence']],
                            input: ['class' => InvoiceRecurrenceInput::class],
                            read: false,
                            name: 'api_invoices_recurrence_put',
                            processor: InvoiceRecurrenceStateProcessor::class,
                        ),
                        new Delete(
                            uriTemplate: '/{invoiceId}/recurrence',
                            status: Response::HTTP_OK,
                            input: false,
                            read: false,
                            deserialize: false,
                            name: 'api_invoices_recurrence_delete',
                            processor: InvoiceRecurrenceDeleteStateProcessor::class,
                        ),
                        new Post(
                            uriTemplate: '/{invoiceId}/installment-plan',
                            status: Response::HTTP_OK,
                            denormalizationContext: ['groups' => ['invoice:installment']],
                            input: ['class' => InvoiceInstallmentPlanInput::class],
                            read: false,
                            name: 'api_invoices_installment_plan_post',
                            processor: InvoiceInstallmentPlanStateProcessor::class,
                        ),
                        new Put(
                            uriTemplate: '/{invoiceId}/installment-plan',
                            status: Response::HTTP_OK,
                            denormalizationContext: ['groups' => ['invoice:installment']],
                            input: ['class' => InvoiceInstallmentPlanInput::class],
                            read: false,
                            name: 'api_invoices_installment_plan_put',
                            processor: InvoiceInstallmentPlanStateProcessor::class,
                        ),
                        new Delete(
                            uriTemplate: '/{invoiceId}/installment-plan',
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
