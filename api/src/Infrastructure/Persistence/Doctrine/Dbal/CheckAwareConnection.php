<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Dbal;

use App\Infrastructure\Persistence\Doctrine\Platform\PostgreSQLCheckPlatform;
use App\Infrastructure\Persistence\Doctrine\Schema\PostgreSQLCheckSchemaManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

final class CheckAwareConnection extends Connection
{
    private ?AbstractPlatform $platform = null;

    private ?AbstractSchemaManager $schemaManager = null;

    public function getDatabasePlatform(): AbstractPlatform
    {
        if ($this->platform) {
            return $this->platform;
        }

        $platform = parent::getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform && !($platform instanceof PostgreSQLCheckPlatform)) {
            $this->platform = new PostgreSQLCheckPlatform();

            return $this->platform;
        }

        return $this->platform = $platform;
    }

    public function createSchemaManager(): AbstractSchemaManager
    {
        if ($this->schemaManager) {
            return $this->schemaManager;
        }

        $platform = $this->getDatabasePlatform();
        if ($platform instanceof PostgreSQLCheckPlatform) {
            return $this->schemaManager = new PostgreSQLCheckSchemaManager($this, $platform);
        }

        return $this->schemaManager = parent::createSchemaManager();
    }
}
