<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Command\UpdateInvoiceCommand;
use App\Application\UseCase\Invoice\Input\InvoiceInput;
use App\Application\UseCase\Invoice\Input\Mapper\InvoicePayloadMapper;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Domain\Contracts\CustomerRepositoryInterface;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Entity\User\User;
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
        private CustomerRepositoryInterface $customerRepository,
        private UserRepositoryInterface $userRepository,
        private InvoicePayloadMapper $payloadMapper,
        private InvoiceOutputMapper $outputMapper,
        #[Autowire(service: 'state_machine.invoice_flow')]
        private WorkflowInterface $invoiceWorkflow,
    ) {
    }

    public function handle(object $data): InvoiceOutput
    {
        $command = TypeGuard::assertClass(UpdateInvoiceCommand::class, $data);
        $invoice = $this->invoiceRepository->findOneById(Uuid::fromString($command->invoiceId));

        if (!$invoice instanceof Invoice) {
            throw new ResourceNotFoundException('Invoice', $command->invoiceId);
        }

        if (InvoiceStatus::DRAFT !== $invoice->status) {
            throw new BadRequestHttpException('Only draft invoices can be updated.');
        }

        $input = $command->input;
        $customer = $this->loadCustomer($input);
        $user = $this->loadUser($input->userId);

        $payload = $this->payloadMapper->map($input, $customer, $user);
        $invoice->applyPayload($payload);

        $this->invoiceRepository->save($invoice);

        return $this->outputMapper->map(
            $invoice,
            $this->availableActions($invoice)
        );
    }

    private function loadCustomer(InvoiceInput $input): Customer
    {
        $customer = $this->customerRepository->findOneById(Uuid::fromString($input->customerId));

        if (null === $customer) {
            throw new ResourceNotFoundException('Customer', $input->customerId);
        }

        return $customer;
    }

    private function loadUser(string $id): User
    {
        $user = $this->userRepository->findOneById(Uuid::fromString($id));

        if (null === $user) {
            throw new ResourceNotFoundException('User', $id);
        }

        return $user;
    }

    /**
     * @return list<string>
     */
    private function availableActions(Invoice $invoice): array
    {
        return array_values(
            array_map(
                static fn ($transition) => $transition->getName(),
                $this->invoiceWorkflow->getEnabledTransitions($invoice)
            )
        );
    }
}
