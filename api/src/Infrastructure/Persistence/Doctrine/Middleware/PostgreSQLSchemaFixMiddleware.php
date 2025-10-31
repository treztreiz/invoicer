<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Middleware;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsMiddleware;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

#[AsMiddleware(priority: 100)]
class PostgreSQLSchemaFixMiddleware implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new class($driver) extends AbstractDriverMiddleware {
        };
    }
}
