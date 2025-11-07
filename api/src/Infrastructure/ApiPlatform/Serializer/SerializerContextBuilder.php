<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Serializer;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\SerializerContextBuilderInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpFoundation\Request;

#[AsDecorator(decorates: 'api_platform.serializer.context_builder')]
final readonly class SerializerContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(
        #[AutowireDecorated]
        private SerializerContextBuilderInterface $decorated,
    ) {
    }

    /** @param array<string, mixed> $extractedAttributes */
    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);

        if (!$normalization) {
            return $context;
        }

        $operationPreference = $this->detectOperationSkipNullPreference($context['operation'] ?? null);

        if (null === $operationPreference) {
            $context['skip_null_values'] = false;
        }

        return $context;
    }

    private function detectOperationSkipNullPreference(?Operation $operation): ?bool
    {
        if (null === $operation) {
            return null;
        }

        $normalizationContext = $operation->getNormalizationContext();
        if (!is_array($normalizationContext)) {
            return null;
        }

        if (!array_key_exists('skip_null_values', $normalizationContext)) {
            return null;
        }

        return (bool) $normalizationContext['skip_null_values'];
    }
}
