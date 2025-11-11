<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\InvoiceGuard;
use App\Application\Guard\TypeGuard;
use App\Application\Service\EntityFetcher;
use App\Application\UseCase\Invoice\Command\UpdateInvoiceCommand;
use App\Application\UseCase\Invoice\Input\Mapper\InvoicePayloadMapper;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\Workflow\WorkflowActionsHelper;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Enum\InvoiceStatus;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

/** @implements UseCaseHandlerInterface<UpdateInvoiceCommand, InvoiceOutput> */
final readonly class UpdateInvoiceHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private InvoicePayloadMapper $payloadMapper,
        private InvoiceOutputMapper $outputMapper,
        private EntityFetcher $entityFetcher,
        #[Autowire(service: 'state_machine.invoice_flow')]
        private WorkflowInterface $invoiceWorkflow,
        private WorkflowActionsHelper $actionsHelper,
    ) {
    }

    public function handle(object $data): InvoiceOutput
    {
        $command = TypeGuard::assertClass(UpdateInvoiceCommand::class, $data);
        $invoice = $this->invoiceRepository->findOneById(Uuid::fromString($command->invoiceId));
        $invoice = InvoiceGuard::assertFound($invoice, $command->invoiceId);

        if (InvoiceStatus::DRAFT !== $invoice->status) {
            throw new BadRequestHttpException('Only draft invoices can be updated.');
        }

        $input = $command->input;
        $customer = $this->entityFetcher->customer($input->customerId);
        $user = $this->entityFetcher->user($input->userId);

        $payload = $this->payloadMapper->map($input, $customer, $user);
        $invoice->applyPayload($payload);

        $this->invoiceRepository->save($invoice);

        return $this->outputMapper->map(
            $invoice,
            $this->actionsHelper->availableActions($invoice, $this->invoiceWorkflow)
        );
    }
}
