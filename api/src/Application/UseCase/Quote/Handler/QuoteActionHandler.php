<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Quote\Command\QuoteActionCommand;
use App\Application\UseCase\Quote\Output\Mapper\QuoteOutputMapper;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Domain\Contracts\QuoteRepositoryInterface;
use App\Domain\Entity\Document\Quote;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

/** @implements UseCaseHandlerInterface<QuoteActionCommand, QuoteOutput> */
final readonly class QuoteActionHandler implements UseCaseHandlerInterface
{
    private const string ACTION_SEND = 'send';
    private const string ACTION_ACCEPT = 'accept';
    private const string ACTION_REJECT = 'reject';

    public function __construct(
        private QuoteRepositoryInterface $quoteRepository,
        private QuoteOutputMapper $outputMapper,
        #[Autowire(service: 'state_machine.quote_flow')]
        private WorkflowInterface $quoteWorkflow,
    ) {
    }

    public function handle(object $data): QuoteOutput
    {
        $command = TypeGuard::assertClass(QuoteActionCommand::class, $data);

        $quote = $this->quoteRepository->findOneById(Uuid::fromString($command->quoteId));

        if (!$quote instanceof Quote) {
            throw new ResourceNotFoundException('Quote', $command->quoteId);
        }

        $transition = $command->action;

        if (!$this->quoteWorkflow->can($quote, $transition)) {
            throw new BadRequestHttpException(sprintf('Quote cannot transition via "%s".', $transition));
        }

        $this->applyTransition($quote, $transition);
        $this->quoteRepository->save($quote);

        $availableActions = array_values(
            array_map(
                static fn ($enabled) => $enabled->getName(),
                $this->quoteWorkflow->getEnabledTransitions($quote)
            )
        );

        return $this->outputMapper->map($quote, $availableActions);
    }

    private function applyTransition(Quote $quote, string $transition): void
    {
        $now = new \DateTimeImmutable();

        match ($transition) {
            self::ACTION_SEND => $quote->send($now),
            self::ACTION_ACCEPT => $quote->markAccepted($now),
            self::ACTION_REJECT => $quote->markRejected($now),
            default => throw new BadRequestHttpException(sprintf('Unknown action "%s".', $transition)),
        };
    }
}
