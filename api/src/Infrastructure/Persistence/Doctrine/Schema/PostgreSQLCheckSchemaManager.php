<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Schema;

use App\Infrastructure\Persistence\Doctrine\Platform\PostgreSQLCheckPlatform;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use Doctrine\DBAL\Schema\Schema;

final class PostgreSQLCheckSchemaManager extends PostgreSQLSchemaManager
{
    private readonly CheckIntrospector $introspector;

    public function __construct(
        private readonly Connection $connection,
        /** @var PostgreSQLCheckPlatform */
        private readonly PostgreSQLCheckPlatform $platform,
        private readonly CheckOptionManager $optionManager,
    ) {
        parent::__construct($connection, $platform);
        $this->introspector = new CheckIntrospector($platform->generator, $this->optionManager);
    }

    /** @throws Exception */
    public function introspectSchema(): Schema
    {
        $schema = parent::introspectSchema();
        $checks = $this->introspector->introspect($this->connection);

        return $this->introspector->annotate($schema, $checks);
    }

    public function createComparator(): Comparator
    {
        return new CheckComparator(parent::createComparator(), $this->platform, $this->optionManager);
    }
}
