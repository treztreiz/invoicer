<?php

namespace App\Infrastructure\Persistence\Doctrine\Schema;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;

trait CheckAwareSchemaManagerTrait
{
    /** @throws Exception */
    public function introspectSchema(): Schema
    {
        $schema = parent::introspectSchema();

        $introspector = new CheckIntrospector($this->platform->generator, $this->platform->optionManager);
        $checks = $introspector->introspect($this->connection);

        return $introspector->annotate($schema, $checks);
    }

    public function createComparator(): Comparator
    {
        return new CheckComparator(parent::createComparator(), $this->platform);
    }
}