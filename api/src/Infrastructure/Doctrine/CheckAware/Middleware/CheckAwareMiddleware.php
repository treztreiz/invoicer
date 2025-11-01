<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Middleware;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckAwarePlatformInterface;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class CheckAwareMiddleware implements Middleware
{
    public function __construct(
        #[AutowireIterator(CheckAwarePlatformInterface::class)]
        private iterable $checkAwarePlatforms,
    ) {
    }

    public function wrap(Driver $driver): Driver
    {
        return new CheckAwareDriverMiddleware($driver, $this->checkAwarePlatforms);
    }
}
