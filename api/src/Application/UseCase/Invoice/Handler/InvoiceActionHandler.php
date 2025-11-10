<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Command\InvoiceActionCommand;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\Workflow\WorkflowActionsHelper;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Entity\Document\Invoice;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

/** @implements UseCaseHandlerInterface<InvoiceActionCommand, InvoiceOutput> */
final readonly class InvoiceActionHandler implements UseCaseHandlerInterface
{
    private const string ACTION_ISSUE = 'issue';
    private const string ACTION_MARK_PAID = 'mark_paid';
    private const string ACTION_VOID = 'void';

    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private InvoiceOutputMapper $outputMapper,
        #[Autowire(service: 'state_machine.invoice_flow')]
        private WorkflowInterface $invoiceWorkflow,
        private WorkflowActionsHelper $actionsHelper,
    ) {
    }

    public function handle(object $data): InvoiceOutput
    {
        $command = TypeGuard::assertClass(InvoiceActionCommand::class, $data);

        $invoice = $this->invoiceRepository->findOneById(Uuid::fromString($command->invoiceId));

        if (!$invoice instanceof Invoice) {
            throw new ResourceNotFoundException('Invoice', $command->invoiceId);
        }

        if (!$this->invoiceWorkflow->can($invoice, $command->action)) {
            throw new BadRequestHttpException(sprintf('Invoice cannot transition via "%s".', $command->action));
        }

        $this->applyTransition($invoice, $command->action);
        $this->invoiceRepository->save($invoice);

        return $this->outputMapper->map($invoice, $this->actionsHelper->availableActions($invoice, $this->invoiceWorkflow));
    }

    private function applyTransition(Invoice $invoice, string $action): void
    {
        $now = new \DateTimeImmutable();

        match ($action) {
            self::ACTION_ISSUE => $invoice->issue($now, $invoice->dueDate ?? $now),
            self::ACTION_MARK_PAID => $invoice->markPaid($now),
            self::ACTION_VOID => $invoice->void(),
            default => throw new BadRequestHttpException(sprintf('Unknown action "%s".', $action)),
        };
    }
}
