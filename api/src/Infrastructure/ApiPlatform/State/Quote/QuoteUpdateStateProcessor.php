<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Quote;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Quote\Handler\UpdateQuoteHandler;
use App\Application\UseCase\Quote\Input\QuoteInput;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Application\UseCase\Quote\Task\UpdateQuoteTask;
use App\Infrastructure\Security\SecurityGuard;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<QuoteInput, QuoteOutput>
 */
final readonly class QuoteUpdateStateProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private UpdateQuoteHandler $handler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): QuoteOutput
    {
        $input = TypeGuard::assertClass(QuoteInput::class, $data);
        $user = SecurityGuard::assertAuth($this->security->getUser());
        $quoteId = (string) ($uriVariables['quoteId'] ?? '');

        if ('' === $quoteId) {
            throw new \InvalidArgumentException('Quote id is required.');
        }

        $input->userId = $user->domainUser->id->toRfc4122();

        return $this->handler->handle(new UpdateQuoteTask($quoteId, $input));
    }
}
