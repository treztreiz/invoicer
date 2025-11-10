<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Quote\Command\UpdateQuoteCommand;
use App\Application\UseCase\Quote\Input\Mapper\QuotePayloadMapper;
use App\Application\UseCase\Quote\Output\Mapper\QuoteOutputMapper;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Domain\Contracts\CustomerRepositoryInterface;
use App\Domain\Contracts\QuoteRepositoryInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entity\Document\Quote;
use App\Domain\Entity\User\User;
use App\Domain\Enum\QuoteStatus;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

/** @implements UseCaseHandlerInterface<UpdateQuoteCommand, QuoteOutput> */
final readonly class UpdateQuoteHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private QuoteRepositoryInterface $quoteRepository,
        private CustomerRepositoryInterface $customerRepository,
        private UserRepositoryInterface $userRepository,
        private QuotePayloadMapper $payloadMapper,
        private QuoteOutputMapper $outputMapper,
        #[Autowire(service: 'state_machine.quote_flow')]
        private WorkflowInterface $quoteWorkflow,
    ) {
    }

    public function handle(object $data): QuoteOutput
    {
        $command = TypeGuard::assertClass(UpdateQuoteCommand::class, $data);
        $quote = $this->quoteRepository->findOneById(Uuid::fromString($command->quoteId));

        if (!$quote instanceof Quote) {
            throw new ResourceNotFoundException('Quote', $command->quoteId);
        }

        if (QuoteStatus::DRAFT !== $quote->status) {
            throw new BadRequestHttpException('Only draft quotes can be updated.');
        }

        $input = $command->input;
        $customer = $this->customerRepository->findOneById(Uuid::fromString($input->customerId));
        if (null === $customer) {
            throw new ResourceNotFoundException('Customer', $input->customerId);
        }

        $user = $this->loadUser($input->userId);

        $payload = $this->payloadMapper->map($input, $customer, $user);
        $quote->applyPayload($payload);

        $this->quoteRepository->save($quote);

        return $this->outputMapper->map(
            $quote,
            $this->availableActions($quote)
        );
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
    private function availableActions(Quote $quote): array
    {
        return array_values(
            array_map(
                static fn ($transition) => $transition->getName(),
                $this->quoteWorkflow->getEnabledTransitions($quote)
            )
        );
    }
}
