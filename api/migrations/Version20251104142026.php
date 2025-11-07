<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251104142026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user ADD logo_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD logo_original_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD logo_size INT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD logo_mime_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD logo_dimensions JSON DEFAULT NULL');
        $this->addSql('DO $$
        BEGIN
        IF NOT EXISTS (
          SELECT 1
          FROM pg_constraint c
          JOIN pg_class r  ON r.oid = c.conrelid
          JOIN pg_namespace n ON n.oid = r.relnamespace
          WHERE c.conname = \'CHK_DOCUMENT_LINE_RATE_UNIT\'
            AND r.relname = \'document_line\'
            AND n.nspname = current_schema()
        ) THEN
          EXECUTE \'ALTER TABLE document_line ADD CONSTRAINT "CHK_DOCUMENT_LINE_RATE_UNIT" CHECK ("rate_unit" = ANY(ARRAY[\'\'HOURLY\'\'::text, \'\'DAILY\'\'::text]))\';
        END IF;
        END$$');
        $this->addSql('DO $$
        BEGIN
        IF NOT EXISTS (
          SELECT 1
          FROM pg_constraint c
          JOIN pg_class r  ON r.oid = c.conrelid
          JOIN pg_namespace n ON n.oid = r.relnamespace
          WHERE c.conname = \'CHK_INVOICE_STATUS\'
            AND r.relname = \'invoice\'
            AND n.nspname = current_schema()
        ) THEN
          EXECUTE \'ALTER TABLE invoice ADD CONSTRAINT "CHK_INVOICE_STATUS" CHECK ("status" = ANY(ARRAY[\'\'DRAFT\'\'::text, \'\'ISSUED\'\'::text, \'\'OVERDUE\'\'::text, \'\'PAID\'\'::text, \'\'VOIDED\'\'::text]))\';
        END IF;
        END$$');
        $this->addSql('DO $$
        BEGIN
        IF NOT EXISTS (
          SELECT 1
          FROM pg_constraint c
          JOIN pg_class r  ON r.oid = c.conrelid
          JOIN pg_namespace n ON n.oid = r.relnamespace
          WHERE c.conname = \'CHK_INVOICE_SCHEDULE_XOR\'
            AND r.relname = \'invoice\'
            AND n.nspname = current_schema()
        ) THEN
          EXECUTE \'ALTER TABLE invoice ADD CONSTRAINT "CHK_INVOICE_SCHEDULE_XOR" CHECK (num_nonnulls("recurrence_id", "installment_plan_id") <= 1)\';
        END IF;
        END$$');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT IF EXISTS "SOFT_XOR"');
        $this->addSql('DO $$
        BEGIN
        IF NOT EXISTS (
          SELECT 1
          FROM pg_constraint c
          JOIN pg_class r  ON r.oid = c.conrelid
          JOIN pg_namespace n ON n.oid = r.relnamespace
          WHERE c.conname = \'CHK_RECURRENCE_FREQUENCY\'
            AND r.relname = \'invoice_recurrence\'
            AND n.nspname = current_schema()
        ) THEN
          EXECUTE \'ALTER TABLE invoice_recurrence ADD CONSTRAINT "CHK_RECURRENCE_FREQUENCY" CHECK ("frequency" = ANY(ARRAY[\'\'MONTHLY\'\'::text, \'\'QUARTERLY\'\'::text]))\';
        END IF;
        END$$');
        $this->addSql('DO $$
        BEGIN
        IF NOT EXISTS (
          SELECT 1
          FROM pg_constraint c
          JOIN pg_class r  ON r.oid = c.conrelid
          JOIN pg_namespace n ON n.oid = r.relnamespace
          WHERE c.conname = \'CHK_RECURRENCE_END_STRATEGY\'
            AND r.relname = \'invoice_recurrence\'
            AND n.nspname = current_schema()
        ) THEN
          EXECUTE \'ALTER TABLE invoice_recurrence ADD CONSTRAINT "CHK_RECURRENCE_END_STRATEGY" CHECK ("end_strategy" = ANY(ARRAY[\'\'UNTIL_DATE\'\'::text, \'\'UNTIL_COUNT\'\'::text, \'\'NEVER\'\'::text]))\';
        END IF;
        END$$');
        $this->addSql('DO $$
        BEGIN
        IF NOT EXISTS (
          SELECT 1
          FROM pg_constraint c
          JOIN pg_class r  ON r.oid = c.conrelid
          JOIN pg_namespace n ON n.oid = r.relnamespace
          WHERE c.conname = \'CHK_QUOTE_STATUS\'
            AND r.relname = \'quote\'
            AND n.nspname = current_schema()
        ) THEN
          EXECUTE \'ALTER TABLE quote ADD CONSTRAINT "CHK_QUOTE_STATUS" CHECK ("status" = ANY(ARRAY[\'\'DRAFT\'\'::text, \'\'SENT\'\'::text, \'\'ACCEPTED\'\'::text, \'\'REJECTED\'\'::text]))\';
        END IF;
        END$$');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user DROP logo_name');
        $this->addSql('ALTER TABLE app_user DROP logo_original_name');
        $this->addSql('ALTER TABLE app_user DROP logo_size');
        $this->addSql('ALTER TABLE app_user DROP logo_mime_type');
        $this->addSql('ALTER TABLE app_user DROP logo_dimensions');
    }
}
