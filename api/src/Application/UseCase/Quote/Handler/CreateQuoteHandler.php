<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Quote\Input\Mapper\QuotePayloadMapper;
use App\Application\UseCase\Quote\Input\QuoteInput;
use App\Application\UseCase\Quote\Output\Mapper\QuoteOutputMapper;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Application\Service\EntityFetcher;
use App\Application\Workflow\WorkflowActionsHelper;
use App\Domain\Contracts\QuoteRepositoryInterface;
use App\Domain\Entity\Document\Quote;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\WorkflowInterface;

/** @implements UseCaseHandlerInterface<QuoteInput, QuoteOutput> */
final readonly class CreateQuoteHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private QuoteRepositoryInterface $quoteRepository,
        private QuotePayloadMapper $mapper,
        private QuoteOutputMapper $outputMapper,
        private EntityFetcher $entityFetcher,
        #[Autowire(service: 'state_machine.quote_flow')]
        private WorkflowInterface $quoteWorkflow,
        private WorkflowActionsHelper $actionsHelper,
    ) {
    }

    public function handle(object $data): QuoteOutput
    {
        $input = TypeGuard::assertClass(QuoteInput::class, $data);

        $customer = $this->entityFetcher->customer($input->customerId);
        $user = $this->entityFetcher->user($input->userId);

        $payload = $this->mapper->map($input, $customer, $user);

        $quote = Quote::fromPayload($payload);

        $this->quoteRepository->save($quote);

        return $this->outputMapper->map(
            $quote,
            $this->actionsHelper->availableActions($quote, $this->quoteWorkflow)
        );
    }

}
