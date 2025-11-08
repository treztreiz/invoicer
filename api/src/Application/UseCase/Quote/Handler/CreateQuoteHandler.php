<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Quote\Input\Mapper\CreateQuoteMapper;
use App\Application\UseCase\Quote\Input\QuoteInput;
use App\Application\UseCase\Quote\Output\Mapper\QuoteOutputMapper;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Domain\Contracts\CustomerRepositoryInterface;
use App\Domain\Contracts\QuoteRepositoryInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entity\Document\Quote;
use App\Domain\Entity\User\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

/** @implements UseCaseHandlerInterface<QuoteInput, QuoteOutput> */
final readonly class CreateQuoteHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private UserRepositoryInterface $userRepository,
        private QuoteRepositoryInterface $quoteRepository,
        private CreateQuoteMapper $mapper,
        private QuoteOutputMapper $outputMapper,
        #[Autowire(service: 'state_machine.quote_flow')]
        private WorkflowInterface $quoteWorkflow,
    ) {
    }

    public function handle(object $data): QuoteOutput
    {
        $input = TypeGuard::assertClass(QuoteInput::class, $data);

        $customer = $this->customerRepository->findOneById(Uuid::fromString($input->customerId));
        if (null === $customer) {
            throw new ResourceNotFoundException('Customer', $input->customerId);
        }

        $user = $this->loadUser($input->userId);

        $payload = $this->mapper->map($input, $customer, $user);

        $quote = Quote::fromPayload($payload);

        $this->quoteRepository->save($quote);

        $availableActions = $this->availableActions($quote);

        return $this->outputMapper->map($quote, $availableActions);
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
