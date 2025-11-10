<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Input\Mapper\CreateInvoiceMapper;
use App\Application\UseCase\Invoice\Input\InvoiceInput;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Domain\Contracts\CustomerRepositoryInterface;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Entity\User\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\Uid\Uuid;

/** @implements UseCaseHandlerInterface<InvoiceInput, InvoiceOutput> */
final readonly class CreateInvoiceHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private UserRepositoryInterface $userRepository,
        private InvoiceRepositoryInterface $invoiceRepository,
        private CreateInvoiceMapper $mapper,
        private InvoiceOutputMapper $outputMapper,
        #[Autowire(service: 'state_machine.invoice_flow')]
        private WorkflowInterface $invoiceWorkflow,
    ) {
    }

    public function handle(object $data): InvoiceOutput
    {
        $input = TypeGuard::assertClass(InvoiceInput::class, $data);

        $customer = $this->loadCustomer($input->customerId);
        $user = $this->loadUser($input->userId);

        $payload = $this->mapper->map($input, $customer, $user);

        $invoice = Invoice::fromPayload($payload);

        $this->invoiceRepository->save($invoice);

        return $this->outputMapper->map($invoice, $this->availableActions($invoice));
    }

    private function loadCustomer(string $id): Customer
    {
        $customer = $this->customerRepository->findOneById(Uuid::fromString($id));

        if (null === $customer) {
            throw new ResourceNotFoundException('Customer', $id);
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
        return array_map(
            static fn ($transition) => $transition->getName(),
            $this->invoiceWorkflow->getEnabledTransitions($invoice)
        );
    }
}
