<?php

// src/Infrastructure/Persistence/Doctrine/Schema/CheckIntrospector.php
declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Schema\Service;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckGeneratorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;

final readonly class CheckIntrospector
{
    public function __construct(
        private CheckGeneratorInterface $generator,
        private CheckRegistry $registry,
    ) {
    }

    /**
     * @return array<string, array<string, string>> table => [constraint => expression]
     *
     * @throws Exception
     */
    public function introspect(Connection $conn): array
    {
        $sql = $this->generator->buildIntrospectionSQL(); // Generate dialect specific SQL
        $checkRows = $conn->fetchAllAssociative($sql); // Retrieve all rows with check
        $checks = [];

        /** @var array{table_name: string, name: string, def: string} $row */
        foreach ($checkRows as $row) {
            $mappedCheck = $this->generator->mapIntrospectionRow($row);
            $checks[$mappedCheck['table']][$mappedCheck['name']] = $mappedCheck['expr'];
        }

        return $checks;
    }

    /** @param array<string, array<string, string>> $checks */
    public function annotate(Schema $schema, array $checks): Schema
    {
        foreach ($schema->getTables() as $table) {
            $tableName = $table->getName();
            if (isset($checks[$tableName])) {
                $this->registry->setIntrospectedExpressions($table, $checks[$tableName]);
            }
        }

        return $schema;
    }
}
