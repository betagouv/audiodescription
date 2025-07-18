<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250715152541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE patrimony.movie ADD tf1_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE patrimony.movie ADD imdb_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE patrimony.movie ADD plurimedia_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A4709120E694EF7F ON patrimony.movie (tf1_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A470912053B538EB ON patrimony.movie (imdb_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A47091209A6C8578 ON patrimony.movie (plurimedia_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_A4709120E694EF7F');
        $this->addSql('DROP INDEX UNIQ_A470912053B538EB');
        $this->addSql('DROP INDEX UNIQ_A47091209A6C8578');
        $this->addSql('ALTER TABLE patrimony.movie DROP tf1_id');
        $this->addSql('ALTER TABLE patrimony.movie DROP imdb_id');
        $this->addSql('ALTER TABLE patrimony.movie DROP plurimedia_id');
    }
}
