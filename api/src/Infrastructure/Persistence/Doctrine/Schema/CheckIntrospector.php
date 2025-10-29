<?php

// src/Infrastructure/Persistence/Doctrine/Schema/CheckIntrospector.php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Schema;

use App\Infrastructure\Persistence\Doctrine\Contracts\CheckGeneratorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;

final readonly class CheckIntrospector
{
    public function __construct(
        private CheckGeneratorInterface $generator,
        private CheckOptionManager $optionManager,
    ) {
    }

    /**
     * @return array<string, list<array{name: string, expr: string}>>
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
            $checks[$mappedCheck['table']][] = ['name' => $mappedCheck['name'], 'expr' => $mappedCheck['expr']];
        }

        return $checks;
    }

    /** @param array<string, list<array{name: string, expr: string}>> $checks */
    public function annotate(Schema $schema, array $checks): Schema
    {
        foreach ($schema->getTables() as $table) {
            $tableName = $table->getName();
            if (isset($checks[$tableName])) {
                $this->optionManager->setExisting($table, $checks[$tableName]);
            }
        }

        return $schema;
    }
}
