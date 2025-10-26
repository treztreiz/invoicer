<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251026151228 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document ADD total_net_value NUMERIC(12, 2) NOT NULL');
        $this->addSql('ALTER TABLE document ADD total_tax_value NUMERIC(12, 2) NOT NULL');
        $this->addSql('ALTER TABLE document ADD total_gross_value NUMERIC(12, 2) NOT NULL');
        $this->addSql('ALTER TABLE document DROP subtotal_net_value');
        $this->addSql('ALTER TABLE document DROP tax_total_value');
        $this->addSql('ALTER TABLE document DROP grand_total_value');
        $this->addSql('ALTER TABLE document_line ADD rate_unit VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE document_line RENAME COLUMN unit_price_value TO rate_value');
        $this->addSql('ALTER TABLE number_sequence ALTER next_value TYPE INT');
        $this->addSql('ALTER INDEX uniq_d29422d42b6adbbabb827337 RENAME TO UNIQ_NUMBER_SEQUENCE_DOC_YEAR');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE document_line DROP rate_unit');
        $this->addSql('ALTER TABLE document_line RENAME COLUMN rate_value TO unit_price_value');
        $this->addSql('ALTER TABLE number_sequence ALTER next_value TYPE BIGINT');
        $this->addSql('ALTER INDEX uniq_number_sequence_doc_year RENAME TO uniq_d29422d42b6adbbabb827337');
        $this->addSql('ALTER TABLE document ADD subtotal_net_value NUMERIC(12, 2) NOT NULL');
        $this->addSql('ALTER TABLE document ADD tax_total_value NUMERIC(12, 2) NOT NULL');
        $this->addSql('ALTER TABLE document ADD grand_total_value NUMERIC(12, 2) NOT NULL');
        $this->addSql('ALTER TABLE document DROP total_net_value');
        $this->addSql('ALTER TABLE document DROP total_tax_value');
        $this->addSql('ALTER TABLE document DROP total_gross_value');
    }
}
