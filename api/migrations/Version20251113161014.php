<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251113161014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document ADD customer_id UUID NOT NULL');
        $this->addSql('COMMENT ON COLUMN document.customer_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A769395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D8698A769395C3F3 ON document (customer_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document DROP CONSTRAINT FK_D8698A769395C3F3');
        $this->addSql('DROP INDEX IDX_D8698A769395C3F3');
        $this->addSql('ALTER TABLE document DROP customer_id');
    }
}
