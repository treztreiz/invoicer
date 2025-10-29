<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Dbal;

use App\Infrastructure\Persistence\Doctrine\Platform\PostgreSQLCheckPlatform;
use App\Infrastructure\Persistence\Doctrine\Schema\CheckOptionManager;
use App\Infrastructure\Persistence\Doctrine\Schema\PostgreSQLCheckSchemaManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

final class CheckAwareConnection extends Connection
{
    private ?AbstractPlatform $platform = null;

    /** @var AbstractSchemaManager<AbstractPlatform>|null */
    private ?AbstractSchemaManager $schemaManager = null;

    private ?CheckOptionManager $checkOptionManager = null;

    private function getCheckOptionManager(): CheckOptionManager
    {
        return $this->checkOptionManager ??= new CheckOptionManager();
    }

    public function getDatabasePlatform(): AbstractPlatform
    {
        if ($this->platform) {
            return $this->platform;
        }

        $platform = parent::getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform && !($platform instanceof PostgreSQLCheckPlatform)) {
            $this->platform = new PostgreSQLCheckPlatform($this->getCheckOptionManager());

            return $this->platform;
        }

        return $this->platform = $platform;
    }

    /**
     * @return AbstractSchemaManager<AbstractPlatform>
     *
     * @throws Exception
     */
    public function createSchemaManager(): AbstractSchemaManager
    {
        if ($this->schemaManager) {
            return $this->schemaManager;
        }

        $platform = $this->getDatabasePlatform();
        if ($platform instanceof PostgreSQLCheckPlatform) {
            return $this->schemaManager = new PostgreSQLCheckSchemaManager(
                $this,
                $platform,
                $this->getCheckOptionManager(),
            );
        }

        return $this->schemaManager = parent::createSchemaManager();
    }
}
