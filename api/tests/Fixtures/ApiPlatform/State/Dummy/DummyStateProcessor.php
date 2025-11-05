<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\ApiPlatform\State\Dummy;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Tests\Fixtures\ApiPlatform\UseCase\Dummy\Command\DummyCommand;
use App\Tests\Fixtures\ApiPlatform\UseCase\Dummy\Result\DummyResult;

final class DummyStateProcessor implements ProcessorInterface
{
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): DummyResult
    {
        if ($data instanceof DummyCommand) {
            return new DummyResult(id: 'demo-id', name: $data->name);
        }

        return new DummyResult(id: 'demo-id');
    }
}
