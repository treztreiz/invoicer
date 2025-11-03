<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Schema\Service;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckGeneratorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;

/** @phpstan-import-type IntrospectedExpressionMap from CheckRegistry */
final readonly class CheckIntrospector
{
    public function __construct(
        private CheckGeneratorInterface $generator,
        private CheckRegistry $registry,
    ) {
    }

    /**
     * @return IntrospectedExpressionMap
     *
     * @throws Exception
     */
    public function introspectDatabase(Connection $conn): array
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

    /** @param IntrospectedExpressionMap $checks */
    public function annotateSchema(Schema $schema, array $checks): Schema
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
