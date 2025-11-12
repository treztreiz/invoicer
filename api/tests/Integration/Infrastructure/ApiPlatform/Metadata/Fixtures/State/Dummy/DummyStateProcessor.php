<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\ApiPlatform\Metadata\Fixtures\State\Dummy;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Tests\Integration\Infrastructure\ApiPlatform\Metadata\Fixtures\UseCase\Dummy\Command\DummyCommand;
use App\Tests\Integration\Infrastructure\ApiPlatform\Metadata\Fixtures\UseCase\Dummy\Result\DummyResult;

/** @implements ProcessorInterface<DummyCommand, DummyResult> */
final class DummyStateProcessor implements ProcessorInterface
{
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): DummyResult
    {
        if ($data->name) {
            return new DummyResult(id: 'demo-id', name: $data->name);
        }

        return new DummyResult(id: 'demo-id');
    }
}
