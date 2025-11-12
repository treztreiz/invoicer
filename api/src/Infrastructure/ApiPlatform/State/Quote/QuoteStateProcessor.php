<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Quote;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Quote\Handler\CreateQuoteHandler;
use App\Application\UseCase\Quote\Input\QuoteInput;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Infrastructure\Security\SecurityGuard;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<QuoteInput, QuoteOutput>
 */
final readonly class QuoteStateProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private CreateQuoteHandler $createQuoteHandler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): QuoteOutput
    {
        $input = TypeGuard::assertClass(QuoteInput::class, $data);
        $user = SecurityGuard::assertAuth($this->security->getUser());

        $input->userId = $user->domainUser->id->toRfc4122();

        return $this->createQuoteHandler->handle($input);
    }
}
