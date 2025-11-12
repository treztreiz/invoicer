<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\ApiPlatform\Metadata\Fixtures\State\Dummy;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Tests\Integration\Infrastructure\ApiPlatform\Metadata\Fixtures\UseCase\Dummy\Result\DummyResult;

/** @implements ProviderInterface<DummyResult> */
final class DummyStateProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): DummyResult
    {
        return new DummyResult(id: 'demo-id', name: 'Demo');
    }
}
