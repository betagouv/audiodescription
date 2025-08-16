<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250813134241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX patrimony.uniq_a47091209a6c8578');
        $this->addSql('DROP INDEX patrimony.uniq_a470912053b538eb');
        $this->addSql('DROP INDEX patrimony.uniq_a4709120e694ef7f');
        $this->addSql('DROP INDEX patrimony.uniq_a4709120e85f6bc8');
        $this->addSql('DROP INDEX patrimony.uniq_a470912011440a98');
        $this->addSql('DROP INDEX patrimony.uniq_a4709120a135ae2f');
        $this->addSql('DROP INDEX patrimony.uniq_a4709120a4c50698');
        $this->addSql('DROP INDEX patrimony.uniq_a47091202e3b8263');
        $this->addSql('DROP INDEX patrimony.uniq_a470912088f1ced7');
        $this->addSql('DROP INDEX patrimony.uniq_a47091206fed69f9');
        $this->addSql('DROP INDEX patrimony.uniq_a4709120a93351b4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX uniq_a47091209a6c8578 ON patrimony.movie (plurimedia_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_a470912053b538eb ON patrimony.movie (imdb_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_a4709120e694ef7f ON patrimony.movie (tf1_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_a4709120e85f6bc8 ON patrimony.movie (isan_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_a470912011440a98 ON patrimony.movie (france_tv_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_a4709120a135ae2f ON patrimony.movie (la_cinetek_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_a4709120a4c50698 ON patrimony.movie (allocine_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_a47091202e3b8263 ON patrimony.movie (orange_vod_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_a470912088f1ced7 ON patrimony.movie (canal_vod_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_a47091206fed69f9 ON patrimony.movie (arte_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_a4709120a93351b4 ON patrimony.movie (cnc_id)');
    }
}
