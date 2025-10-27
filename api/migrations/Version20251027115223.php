<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251027115223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Installment + Recurrence';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            'CREATE TABLE installment (id uuid NOT NULL, installment_plan_id uuid NOT NULL, integer INT NOT NULL, percentage NUMERIC(5, 2) NOT NULL, due_date DATE DEFAULT NULL, generated_invoice_id uuid DEFAULT NULL, amount_net_value NUMERIC(12, 2) NOT NULL, amount_tax_value NUMERIC(12, 2) NOT NULL, amount_gross_value NUMERIC(12, 2) NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('CREATE INDEX idx_4b778acd6810509d ON installment (installment_plan_id)');
        $this->addSql('COMMENT ON COLUMN installment.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN installment.installment_plan_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN installment.due_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN installment.generated_invoice_id IS \'(DC2Type:uuid)\'');
        $this->addSql(
            'CREATE TABLE installment_plan (id uuid NOT NULL, invoice_id uuid NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('CREATE UNIQUE INDEX uniq_512db9482989f1fd ON installment_plan (invoice_id)');
        $this->addSql('COMMENT ON COLUMN installment_plan.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN installment_plan.invoice_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN installment_plan.created_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN installment_plan.updated_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql(
            'CREATE TABLE invoice_recurrence (id uuid NOT NULL, invoice_id uuid NOT NULL, frequency VARCHAR(255) NOT NULL, interval SMALLINT NOT NULL, anchor_date DATE NOT NULL, end_strategy VARCHAR(255) NOT NULL, next_run_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, end_date DATE DEFAULT NULL, occurrence_count SMALLINT DEFAULT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('CREATE UNIQUE INDEX uniq_e66b82bf2989f1fd ON invoice_recurrence (invoice_id)');
        $this->addSql('COMMENT ON COLUMN invoice_recurrence.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN invoice_recurrence.invoice_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN invoice_recurrence.anchor_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN invoice_recurrence.next_run_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN invoice_recurrence.end_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql(
            'ALTER TABLE installment ADD CONSTRAINT fk_4b778acd6810509d FOREIGN KEY (installment_plan_id) REFERENCES installment_plan (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql(
            'ALTER TABLE installment_plan ADD CONSTRAINT fk_512db9482989f1fd FOREIGN KEY (invoice_id) REFERENCES invoice (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql(
            'ALTER TABLE invoice_recurrence ADD CONSTRAINT fk_e66b82bf2989f1fd FOREIGN KEY (invoice_id) REFERENCES invoice (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
        $this->addSql('ALTER TABLE invoice ADD recurrence_seed_id uuid DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice ADD installment_seed_id uuid DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN invoice.recurrence_seed_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN invoice.installment_seed_id IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE installment DROP CONSTRAINT fk_4b778acd6810509d');
        $this->addSql('ALTER TABLE installment_plan DROP CONSTRAINT fk_512db9482989f1fd');
        $this->addSql('ALTER TABLE invoice_recurrence DROP CONSTRAINT fk_e66b82bf2989f1fd');
        $this->addSql('DROP TABLE installment');
        $this->addSql('DROP TABLE installment_plan');
        $this->addSql('DROP TABLE invoice_recurrence');
        $this->addSql('ALTER TABLE invoice DROP recurrence_seed_id');
        $this->addSql('ALTER TABLE invoice DROP installment_seed_id');
    }
}
