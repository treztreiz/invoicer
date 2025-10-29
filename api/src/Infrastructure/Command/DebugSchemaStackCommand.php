<?php

// src/Infrastructure/Command/DebugSchemaStackCommand.php

declare(strict_types=1);

namespace App\Infrastructure\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:db:debug-schema-stack', description: 'Show SchemaManager & Comparator classes and table options.')]
final class DebugSchemaStackCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conn = $this->em->getConnection();
        $sm = $conn->createSchemaManager();

        $output->writeln('SchemaManager: <info>'.get_class($sm).'</info>');

        // Live (from DB)
        $from = $sm->introspectSchema();

        $cmp = $sm->createComparator();
        $output->writeln('Comparator: <info>'.get_class($cmp).'</info>');

        // Target (from metadata) – ensures our ToolEvent listener ran and set options
        $classes = $this->em->getMetadataFactory()->getAllMetadata();
        $to = (new SchemaTool($this->em))->getSchemaFromMetadata($classes);

        // Pick your table name (e.g. 'invoice')
        $tableName = 'invoice';
        if ($to->hasTable($tableName)) {
            $t = $to->getTable($tableName);
            $output->writeln("Target table options for '{$tableName}':");
            foreach (['soft_xor_required', 'soft_xor_name', 'soft_xor_columns'] as $opt) {
                $val = $t->hasOption($opt) ? json_encode($t->getOption($opt)) : 'N/A';
                $output->writeln("  - {$opt}: <comment>{$val}</comment>");
            }
        }

        if ($from->hasTable($tableName)) {
            $t = $from->getTable($tableName);
            $val = $t->hasOption('soft_xor_present') ? 'present' : 'absent';
            $output->writeln("Live table '{$tableName}' soft_xor_present: <comment>{$val}</comment>");
        }

        return Command::SUCCESS;
    }
}
