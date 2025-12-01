<?php

declare(strict_types=1);

namespace App\Tests\Integration\Messenger;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Application\Message\RunRecurringInvoicesMessage;
use App\Tests\Factory\Document\Invoice\InvoiceFactory;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

final class RunRecurringInvoicesMessageHandlerTest extends ApiTestCase
{
    use InteractsWithMessenger;
    use Factories;
    use ResetDatabase;

    protected static ?bool $alwaysBootKernel = true;

    /**
     * @throws ExceptionInterface
     */
    public function test_handler_generates_invoices_from_seed(): void
    {
        InvoiceFactory::new()->withRecurrence([
            'nextRunAt' => new \DateTimeImmutable('yesterday'),
        ])->withSnapshots()->create();

        /** @var MessageBusInterface $bus */
        $bus = self::getContainer()->get(MessageBusInterface::class);
        $bus->dispatch(new RunRecurringInvoicesMessage());

        $this->transport('async')->process();

        InvoiceFactory::assert()->count(2);
    }
}
