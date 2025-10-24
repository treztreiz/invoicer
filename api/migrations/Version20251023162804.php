<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251023162804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Customer';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            'CREATE TABLE customer (id uuid NOT NULL, first_name VARCHAR(150) NOT NULL, last_name VARCHAR(150) NOT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(32) DEFAULT NULL, archived BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, address_street_line1 VARCHAR(255) NOT NULL, address_street_line2 VARCHAR(255) DEFAULT NULL, address_postal_code VARCHAR(20) NOT NULL, address_city VARCHAR(150) NOT NULL, address_region VARCHAR(150) DEFAULT NULL, address_country_code VARCHAR(2) NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('COMMENT ON COLUMN customer.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN customer.created_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN customer.updated_at IS \'(DC2Type:datetimetz_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE customer');
    }
}
