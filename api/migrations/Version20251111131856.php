<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251111131856 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user ADD company_logo_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD company_logo_original_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD company_logo_mime_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user DROP logo_name');
        $this->addSql('ALTER TABLE app_user DROP logo_original_name');
        $this->addSql('ALTER TABLE app_user DROP logo_mime_type');
        $this->addSql('ALTER TABLE app_user RENAME COLUMN logo_size TO company_logo_size');
        $this->addSql('ALTER TABLE app_user RENAME COLUMN logo_dimensions TO company_logo_dimensions');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user ADD logo_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD logo_original_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD logo_mime_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user DROP company_logo_name');
        $this->addSql('ALTER TABLE app_user DROP company_logo_original_name');
        $this->addSql('ALTER TABLE app_user DROP company_logo_mime_type');
        $this->addSql('ALTER TABLE app_user RENAME COLUMN company_logo_size TO logo_size');
        $this->addSql('ALTER TABLE app_user RENAME COLUMN company_logo_dimensions TO logo_dimensions');
    }
}
