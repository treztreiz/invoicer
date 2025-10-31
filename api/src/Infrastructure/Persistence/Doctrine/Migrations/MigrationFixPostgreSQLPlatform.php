<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\SchemaDiff;

class MigrationFixPostgreSQLPlatform extends PostgreSQLPlatform
{
    public function __construct(private readonly PostgreSQLPlatform $defaultPlatform)
    {
    }

    public function getAlterSchemaSQL(SchemaDiff $diff): array
    {
        if (isset($diff->newNamespaces['public'])) {
            unset($diff->newNamespaces['public']);
        }

        return $this->defaultPlatform->getAlterSchemaSQL($diff);
    }
}
