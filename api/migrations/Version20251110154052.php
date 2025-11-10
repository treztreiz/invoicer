<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251110154052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT FK_906517442C414CE8');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT FK_906517446810509D');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517442C414CE8 FOREIGN KEY (recurrence_id) REFERENCES invoice_recurrence (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517446810509D FOREIGN KEY (installment_plan_id) REFERENCES installment_plan (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT fk_906517442c414ce8');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT fk_906517446810509d');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT fk_906517442c414ce8 FOREIGN KEY (recurrence_id) REFERENCES invoice_recurrence (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT fk_906517446810509d FOREIGN KEY (installment_plan_id) REFERENCES installment_plan (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
