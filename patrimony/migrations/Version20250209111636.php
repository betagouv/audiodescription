<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250209111636 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE patrimony.genre ADD main_genre_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN patrimony.genre.main_genre_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE patrimony.genre ADD CONSTRAINT FK_3A7E50B79BB4C26A FOREIGN KEY (main_genre_id) REFERENCES patrimony.genre (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3A7E50B79BB4C26A ON patrimony.genre (main_genre_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE patrimony.genre DROP CONSTRAINT FK_3A7E50B79BB4C26A');
        $this->addSql('DROP INDEX IDX_3A7E50B79BB4C26A');
        $this->addSql('ALTER TABLE patrimony.genre DROP main_genre_id');
    }
}
