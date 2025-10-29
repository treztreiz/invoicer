<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251027221546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE installment_plan DROP CONSTRAINT fk_512db9482989f1fd');
        $this->addSql('DROP INDEX uniq_512db9482989f1fd');
        $this->addSql('ALTER TABLE installment_plan DROP invoice_id');
        $this->addSql('ALTER TABLE invoice ADD recurrence_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice ADD installment_plan_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN invoice.recurrence_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN invoice.installment_plan_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517442C414CE8 FOREIGN KEY (recurrence_id) REFERENCES invoice_recurrence (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517446810509D FOREIGN KEY (installment_plan_id) REFERENCES installment_plan (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_906517442C414CE8 ON invoice (recurrence_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_906517446810509D ON invoice (installment_plan_id)');
        $this->addSql('ALTER TABLE invoice_recurrence DROP CONSTRAINT fk_e66b82bf2989f1fd');
        $this->addSql('DROP INDEX uniq_e66b82bf2989f1fd');
        $this->addSql('ALTER TABLE invoice_recurrence DROP invoice_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE invoice_recurrence ADD invoice_id UUID NOT NULL');
        $this->addSql('COMMENT ON COLUMN invoice_recurrence.invoice_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE invoice_recurrence ADD CONSTRAINT fk_e66b82bf2989f1fd FOREIGN KEY (invoice_id) REFERENCES invoice (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_e66b82bf2989f1fd ON invoice_recurrence (invoice_id)');
        $this->addSql('ALTER TABLE installment_plan ADD invoice_id UUID NOT NULL');
        $this->addSql('COMMENT ON COLUMN installment_plan.invoice_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE installment_plan ADD CONSTRAINT fk_512db9482989f1fd FOREIGN KEY (invoice_id) REFERENCES invoice (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_512db9482989f1fd ON installment_plan (invoice_id)');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT FK_906517442C414CE8');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT FK_906517446810509D');
        $this->addSql('DROP INDEX UNIQ_906517442C414CE8');
        $this->addSql('DROP INDEX UNIQ_906517446810509D');
        $this->addSql('ALTER TABLE invoice DROP recurrence_id');
        $this->addSql('ALTER TABLE invoice DROP installment_plan_id');
    }
}
