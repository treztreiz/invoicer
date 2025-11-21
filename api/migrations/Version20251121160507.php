<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251121160507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX uniq_document_line_document_position ON document_line (document_id, position)');
        $this->addSql('CREATE UNIQUE INDEX uniq_installment_plan_position ON installment (installment_plan_id, integer)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_document_line_document_position');
        $this->addSql('DROP INDEX uniq_installment_plan_position');
    }
}
