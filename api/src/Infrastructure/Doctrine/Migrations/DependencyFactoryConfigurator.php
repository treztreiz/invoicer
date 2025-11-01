<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Migrations;

use Doctrine\DBAL\Exception;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Generator\SqlGenerator;

final class DependencyFactoryConfigurator
{
    /**
     * @throws Exception
     */
    public function __invoke(DependencyFactory $factory): void
    {
        $factory->setDefinition(
            SqlGenerator::class,
            fn (): SqlGenerator => new PostgreSqlSchemaFixSqlGenerator(
                $factory->getConfiguration(),
                $factory->getConnection()->getDatabasePlatform(),
            )
        );
    }
}
