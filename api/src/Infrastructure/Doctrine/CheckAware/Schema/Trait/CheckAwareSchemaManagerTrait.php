<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Schema\Trait;

use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckComparator;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckIntrospector;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;

trait CheckAwareSchemaManagerTrait
{
    /** @throws Exception */
    public function introspectSchema(): Schema
    {
        $schema = parent::introspectSchema();

        $introspector = new CheckIntrospector($this->platform->generator, $this->platform->registry);
        $checks = $introspector->introspectDatabase($this->connection);

        return $introspector->annotateSchema($schema, $checks);
    }

    public function createComparator(): Comparator
    {
        return new CheckComparator(parent::createComparator(), $this->platform);
    }
}
