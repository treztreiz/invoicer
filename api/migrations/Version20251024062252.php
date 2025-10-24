<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251024062252 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'User + Company';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            'CREATE TABLE "user" (id uuid NOT NULL, last_login TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, user_identifier VARCHAR(180) NOT NULL, roles json NOT NULL, password VARCHAR(255) NOT NULL, locale VARCHAR(10) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, first_name VARCHAR(150) NOT NULL, last_name VARCHAR(150) NOT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(32) DEFAULT NULL, company_legal_name VARCHAR(255) NOT NULL, company_default_currency VARCHAR(3) NOT NULL, company_default_hourly_rate NUMERIC(10, 2) NOT NULL, company_default_daily_rate NUMERIC(10, 2) NOT NULL, company_default_vat_rate NUMERIC(5, 2) NOT NULL, company_legal_mention TEXT DEFAULT NULL, company_email VARCHAR(180) DEFAULT NULL, company_phone VARCHAR(32) DEFAULT NULL, company_address_street_line1 VARCHAR(255) NOT NULL, company_address_street_line2 VARCHAR(255) DEFAULT NULL, company_address_postal_code VARCHAR(20) NOT NULL, company_address_city VARCHAR(150) NOT NULL, company_address_region VARCHAR(150) DEFAULT NULL, company_address_country_code VARCHAR(2) NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('CREATE UNIQUE INDEX uniq_identifier_user ON "user" (user_identifier)');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN "user".last_login IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".updated_at IS \'(DC2Type:datetimetz_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE "user"');
    }
}
