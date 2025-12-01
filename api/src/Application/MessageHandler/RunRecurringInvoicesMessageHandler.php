<?php

declare(strict_types=1);

namespace App\Application\MessageHandler;

use App\Application\Message\RunRecurringInvoicesMessage;
use App\Application\UseCase\Invoice\Recurrence\GenerateRecurringInvoiceUseCase;
use App\Domain\Contracts\Repository\InvoiceRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RunRecurringInvoicesMessageHandler
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private GenerateRecurringInvoiceUseCase $useCase,
    ) {
    }

    public function __invoke(RunRecurringInvoicesMessage $message): void
    {
        $seeds = $this->invoiceRepository->findRecurrenceSeeds($message->runDate);

        foreach ($seeds as $seed) {
            $this->useCase->handle($seed->id->toRfc4122());
        }
    }
}
