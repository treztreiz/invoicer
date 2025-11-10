<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\Service\EntityFetcher;
use App\Application\UseCase\Quote\Command\UpdateQuoteCommand;
use App\Application\UseCase\Quote\Input\Mapper\QuotePayloadMapper;
use App\Application\UseCase\Quote\Output\Mapper\QuoteOutputMapper;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Application\Workflow\WorkflowActionsHelper;
use App\Domain\Contracts\QuoteRepositoryInterface;
use App\Domain\Entity\Document\Quote;
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
        private QuotePayloadMapper $payloadMapper,
        private QuoteOutputMapper $outputMapper,
        private EntityFetcher $entityFetcher,
        #[Autowire(service: 'state_machine.quote_flow')]
        private WorkflowInterface $quoteWorkflow,
        private WorkflowActionsHelper $actionsHelper,
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
        $customer = $this->entityFetcher->customer($input->customerId);
        $user = $this->entityFetcher->user($input->userId);

        $payload = $this->payloadMapper->map($input, $customer, $user);
        $quote->applyPayload($payload);

        $this->quoteRepository->save($quote);

        return $this->outputMapper->map(
            $quote,
            $this->actionsHelper->availableActions($quote, $this->quoteWorkflow)
        );
    }
}
