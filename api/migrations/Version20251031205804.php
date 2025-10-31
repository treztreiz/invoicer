<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251031205804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Domain scaffold';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            'CREATE TABLE app_user (id uuid NOT NULL, last_login TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, user_identifier VARCHAR(180) NOT NULL, roles json NOT NULL, password VARCHAR(255) NOT NULL, locale VARCHAR(10) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, first_name VARCHAR(150) NOT NULL, last_name VARCHAR(150) NOT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(32) DEFAULT NULL, company_legal_name VARCHAR(255) NOT NULL, company_default_currency VARCHAR(3) NOT NULL, company_legal_mention TEXT DEFAULT NULL, company_email VARCHAR(180) DEFAULT NULL, company_phone VARCHAR(32) DEFAULT NULL, company_address_street_line1 VARCHAR(255) NOT NULL, company_address_street_line2 VARCHAR(255) DEFAULT NULL, company_address_postal_code VARCHAR(20) NOT NULL, company_address_city VARCHAR(150) NOT NULL, company_address_region VARCHAR(150) DEFAULT NULL, company_address_country_code VARCHAR(2) NOT NULL, company_default_hourly_rate_value NUMERIC(12, 2) NOT NULL, company_default_daily_rate_value NUMERIC(12, 2) NOT NULL, company_default_vat_rate_value NUMERIC(5, 2) NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('CREATE UNIQUE INDEX uniq_identifier_user ON app_user (user_identifier)');
        $this->addSql('COMMENT ON COLUMN app_user.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN app_user.last_login IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN app_user.created_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN app_user.updated_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql(
            'CREATE TABLE customer (id uuid NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, is_archived BOOLEAN NOT NULL, first_name VARCHAR(150) NOT NULL, last_name VARCHAR(150) NOT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(32) DEFAULT NULL, address_street_line1 VARCHAR(255) NOT NULL, address_street_line2 VARCHAR(255) DEFAULT NULL, address_postal_code VARCHAR(20) NOT NULL, address_city VARCHAR(150) NOT NULL, address_region VARCHAR(150) DEFAULT NULL, address_country_code VARCHAR(2) NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('COMMENT ON COLUMN customer.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN customer.created_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN customer.updated_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql(
            'CREATE TABLE document (id uuid NOT NULL, title VARCHAR(200) NOT NULL, currency VARCHAR(3) NOT NULL, customer_snapshot json NOT NULL, company_snapshot json NOT NULL, subtitle VARCHAR(200) DEFAULT NULL, reference VARCHAR(30) DEFAULT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, is_archived BOOLEAN NOT NULL, vat_rate_value NUMERIC(5, 2) NOT NULL, total_net_value NUMERIC(12, 2) NOT NULL, total_tax_value NUMERIC(12, 2) NOT NULL, total_gross_value NUMERIC(12, 2) NOT NULL, type VARCHAR(10) NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('COMMENT ON COLUMN document.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN document.created_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN document.updated_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql(
            'CREATE TABLE document_line (id uuid NOT NULL, document_id uuid NOT NULL, description TEXT NOT NULL, rate_unit VARCHAR(255) NOT NULL, position INT NOT NULL, quantity_value NUMERIC(12, 3) NOT NULL, rate_value NUMERIC(12, 2) NOT NULL, amount_net_value NUMERIC(12, 2) NOT NULL, amount_tax_value NUMERIC(12, 2) NOT NULL, amount_gross_value NUMERIC(12, 2) NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('CREATE INDEX idx_76a03865c33f7837 ON document_line (document_id)');
        $this->addSql('COMMENT ON COLUMN document_line.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN document_line.document_id IS \'(DC2Type:uuid)\'');
        $this->addSql(
            'CREATE TABLE installment (id uuid NOT NULL, installment_plan_id uuid NOT NULL, integer INT NOT NULL, percentage NUMERIC(5, 2) NOT NULL, due_date DATE DEFAULT NULL, generated_invoice_id uuid DEFAULT NULL, amount_net_value NUMERIC(12, 2) NOT NULL, amount_tax_value NUMERIC(12, 2) NOT NULL, amount_gross_value NUMERIC(12, 2) NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('CREATE INDEX idx_4b778acd6810509d ON installment (installment_plan_id)');
        $this->addSql('COMMENT ON COLUMN installment.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN installment.installment_plan_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN installment.due_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN installment.generated_invoice_id IS \'(DC2Type:uuid)\'');
        $this->addSql(
            'CREATE TABLE installment_plan (id uuid NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('COMMENT ON COLUMN installment_plan.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN installment_plan.created_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN installment_plan.updated_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql(
            'CREATE TABLE invoice (id uuid NOT NULL, recurrence_id uuid DEFAULT NULL, installment_plan_id uuid DEFAULT NULL, status VARCHAR(255) NOT NULL, issued_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, due_date DATE DEFAULT NULL, paid_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, recurrence_seed_id uuid DEFAULT NULL, installment_seed_id uuid DEFAULT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('CREATE UNIQUE INDEX uniq_906517442c414ce8 ON invoice (recurrence_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_906517446810509d ON invoice (installment_plan_id)');
        $this->addSql('COMMENT ON COLUMN invoice.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN invoice.recurrence_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN invoice.installment_plan_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN invoice.issued_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN invoice.due_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN invoice.paid_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN invoice.recurrence_seed_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN invoice.installment_seed_id IS \'(DC2Type:uuid)\'');
        $this->addSql(
            'CREATE TABLE invoice_recurrence (id uuid NOT NULL, frequency VARCHAR(255) NOT NULL, interval SMALLINT NOT NULL, anchor_date DATE NOT NULL, end_strategy VARCHAR(255) NOT NULL, next_run_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, end_date DATE DEFAULT NULL, occurrence_count SMALLINT DEFAULT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('COMMENT ON COLUMN invoice_recurrence.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN invoice_recurrence.anchor_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN invoice_recurrence.next_run_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN invoice_recurrence.end_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('CREATE TABLE number_sequence (id uuid NOT NULL, document_type VARCHAR(255) NOT NULL, year SMALLINT NOT NULL, next_value INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_number_sequence_doc_year ON number_sequence (document_type, year)');
        $this->addSql('COMMENT ON COLUMN number_sequence.id IS \'(DC2Type:uuid)\'');
        $this->addSql(
            'CREATE TABLE quote (id uuid NOT NULL, status VARCHAR(255) NOT NULL, sent_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, accepted_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, rejected_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, converted_invoice_id uuid DEFAULT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('COMMENT ON COLUMN quote.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN quote.sent_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN quote.accepted_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN quote.rejected_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN quote.converted_invoice_id IS \'(DC2Type:uuid)\'');
        $this->addSql(
            'ALTER TABLE document_line ADD CONSTRAINT fk_76a03865c33f7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql(
            'ALTER TABLE installment ADD CONSTRAINT fk_4b778acd6810509d FOREIGN KEY (installment_plan_id) REFERENCES installment_plan (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT fk_906517442c414ce8 FOREIGN KEY (recurrence_id) REFERENCES invoice_recurrence (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql(
            'ALTER TABLE invoice ADD CONSTRAINT fk_906517446810509d FOREIGN KEY (installment_plan_id) REFERENCES installment_plan (id) NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT fk_90651744bf396750 FOREIGN KEY (id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT fk_6b71cbf4bf396750 FOREIGN KEY (id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql(
            'DO $$
        BEGIN
        IF NOT EXISTS (
          SELECT 1
          FROM pg_constraint c
          JOIN pg_class r  ON r.oid = c.conrelid
          JOIN pg_namespace n ON n.oid = r.relnamespace
          WHERE c.conname = \'SOFT_XOR\'
            AND r.relname = \'invoice\'
            AND n.nspname = current_schema()
        ) THEN
          EXECUTE \'ALTER TABLE invoice ADD CONSTRAINT "SOFT_XOR" CHECK (num_nonnulls("recurrence_id", "installment_plan_id") <= 1)\';
        END IF;
        END$$'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document_line DROP CONSTRAINT fk_76a03865c33f7837');
        $this->addSql('ALTER TABLE installment DROP CONSTRAINT fk_4b778acd6810509d');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT fk_906517442c414ce8');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT fk_906517446810509d');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT fk_90651744bf396750');
        $this->addSql('ALTER TABLE quote DROP CONSTRAINT fk_6b71cbf4bf396750');
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE document_line');
        $this->addSql('DROP TABLE installment');
        $this->addSql('DROP TABLE installment_plan');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE invoice_recurrence');
        $this->addSql('DROP TABLE number_sequence');
        $this->addSql('DROP TABLE quote');
    }
}
