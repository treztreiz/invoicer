<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251025104130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE document (id UUID NOT NULL, title VARCHAR(200) NOT NULL, subtitle VARCHAR(200) DEFAULT NULL, reference VARCHAR(30) DEFAULT NULL, currency VARCHAR(3) NOT NULL, customer_snapshot JSON NOT NULL, company_snapshot JSON NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, is_archived BOOLEAN NOT NULL, vat_rate_value NUMERIC(5, 2) NOT NULL, subtotal_net_value NUMERIC(12, 2) NOT NULL, tax_total_value NUMERIC(12, 2) NOT NULL, grand_total_value NUMERIC(12, 2) NOT NULL, type VARCHAR(10) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN document.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN document.created_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN document.updated_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('CREATE TABLE document_line (id UUID NOT NULL, document_id UUID NOT NULL, description TEXT NOT NULL, position INT NOT NULL, quantity_value NUMERIC(12, 3) NOT NULL, unit_price_value NUMERIC(12, 2) NOT NULL, amount_net_value NUMERIC(12, 2) NOT NULL, amount_tax_value NUMERIC(12, 2) NOT NULL, amount_gross_value NUMERIC(12, 2) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_76A03865C33F7837 ON document_line (document_id)');
        $this->addSql('COMMENT ON COLUMN document_line.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN document_line.document_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE invoice (id UUID NOT NULL, status VARCHAR(255) NOT NULL, issued_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, due_date DATE DEFAULT NULL, paid_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN invoice.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN invoice.issued_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN invoice.due_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN invoice.paid_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('CREATE TABLE number_sequence (id UUID NOT NULL, document_type VARCHAR(255) NOT NULL, year SMALLINT NOT NULL, next_value BIGINT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D29422D42B6ADBBABB827337 ON number_sequence (document_type, year)');
        $this->addSql('COMMENT ON COLUMN number_sequence.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE quote (id UUID NOT NULL, status VARCHAR(255) NOT NULL, sent_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, accepted_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, rejected_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, converted_invoice_id UUID DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN quote.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN quote.sent_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN quote.accepted_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN quote.rejected_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN quote.converted_invoice_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE document_line ADD CONSTRAINT FK_76A03865C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744BF396750 FOREIGN KEY (id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF4BF396750 FOREIGN KEY (id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE app_user ADD company_default_hourly_rate_value NUMERIC(12, 2) NOT NULL');
        $this->addSql('ALTER TABLE app_user ADD company_default_daily_rate_value NUMERIC(12, 2) NOT NULL');
        $this->addSql('ALTER TABLE app_user DROP company_default_hourly_rate');
        $this->addSql('ALTER TABLE app_user DROP company_default_daily_rate');
        $this->addSql('ALTER TABLE app_user RENAME COLUMN company_default_vat_rate TO company_default_vat_rate_value');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE document_line DROP CONSTRAINT FK_76A03865C33F7837');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT FK_90651744BF396750');
        $this->addSql('ALTER TABLE quote DROP CONSTRAINT FK_6B71CBF4BF396750');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE document_line');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE number_sequence');
        $this->addSql('DROP TABLE quote');
        $this->addSql('ALTER TABLE app_user ADD company_default_hourly_rate NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE app_user ADD company_default_daily_rate NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE app_user DROP company_default_hourly_rate_value');
        $this->addSql('ALTER TABLE app_user DROP company_default_daily_rate_value');
        $this->addSql('ALTER TABLE app_user RENAME COLUMN company_default_vat_rate_value TO company_default_vat_rate');
    }
}
