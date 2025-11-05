<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\ApiPlatform\UseCase\Dummy\Result;

use Symfony\Component\Serializer\Annotation\Groups;

final class DummyResult
{
    public function __construct(
        #[Groups(['demo:read'])]
        public string $id,
        #[Groups(['demo:read'])]
        public ?string $name = null,
    ) {
    }
}
