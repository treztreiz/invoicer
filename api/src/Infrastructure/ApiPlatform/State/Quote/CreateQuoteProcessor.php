<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Quote;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Dto\Quote\Input\QuoteInput;
use App\Application\Dto\Quote\Output\QuoteOutput;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Quote\CreateQuoteUseCase;
use App\Infrastructure\Security\SecurityGuard;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<QuoteInput, QuoteOutput>
 */
final readonly class CreateQuoteProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private CreateQuoteUseCase $createQuoteHandler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): QuoteOutput
    {
        $input = TypeGuard::assertClass(QuoteInput::class, $data);
        $securityUser = SecurityGuard::assertAuth($this->security->getUser());

        return $this->createQuoteHandler->handle(
            input: $input,
            userId: $securityUser->user->id->toRfc4122()
        );
    }
}
