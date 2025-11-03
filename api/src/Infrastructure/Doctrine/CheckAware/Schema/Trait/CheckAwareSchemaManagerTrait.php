<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Schema\Trait;

use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckComparator;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;

trait CheckAwareSchemaManagerTrait
{
    /** @throws Exception */
    public function introspectSchema(): Schema
    {
        $schema = parent::introspectSchema();

        $sql = $this->platform->generator->buildIntrospectionSQL(); // Generate dialect specific SQL
        $checkRows = $this->connection->fetchAllAssociative($sql); // Retrieve all rows with check
        $expressions = [];

        foreach ($checkRows as $row) {
            $mappedRow = $this->platform->generator->mapIntrospectionRow($row);
            $expressions[$mappedRow['table']][$mappedRow['name']] = $mappedRow['expr'];
        }

        $this->platform->registry->registerIntrospectedExpressions($schema, $expressions);

        return $schema;
    }

    public function createComparator(): Comparator
    {
        return new CheckComparator(parent::createComparator(), $this->platform);
    }
}
