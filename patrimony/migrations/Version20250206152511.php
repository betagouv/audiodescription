<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250206152511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA patrimony');
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE patrimony.movie_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE patrimony.source_movie_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE patrimony.actor (id UUID NOT NULL, fullname VARCHAR(255) NOT NULL, firstname VARCHAR(255) DEFAULT NULL, lastname VARCHAR(255) DEFAULT NULL, code VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FD5B35B677153098 ON patrimony.actor (code)');
        $this->addSql('COMMENT ON COLUMN patrimony.actor.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE patrimony.actormovie (id UUID NOT NULL, movie_id INT DEFAULT NULL, actor_id UUID DEFAULT NULL, role VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_84D759E98F93B6FC ON patrimony.actormovie (movie_id)');
        $this->addSql('CREATE INDEX IDX_84D759E910DAF24A ON patrimony.actormovie (actor_id)');
        $this->addSql('COMMENT ON COLUMN patrimony.actormovie.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN patrimony.actormovie.actor_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE patrimony.director (id UUID NOT NULL, fullname VARCHAR(255) NOT NULL, firstname VARCHAR(255) DEFAULT NULL, lastname VARCHAR(255) DEFAULT NULL, code VARCHAR(255) NOT NULL, picture TEXT DEFAULT NULL, biography TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F7502C1F77153098 ON patrimony.director (code)');
        $this->addSql('COMMENT ON COLUMN patrimony.director.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE patrimony.genre (id UUID NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3A7E50B777153098 ON patrimony.genre (code)');
        $this->addSql('COMMENT ON COLUMN patrimony.genre.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE patrimony.language (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN patrimony.language.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE patrimony.movie (id INT NOT NULL, public_id UUID DEFAULT NULL, title VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, cnc_id VARCHAR(255) DEFAULT NULL, arte_id VARCHAR(255) DEFAULT NULL, canal_vod_id VARCHAR(255) DEFAULT NULL, visa VARCHAR(255) DEFAULT NULL, has_ad BOOLEAN NOT NULL, poster TEXT DEFAULT NULL, synopsis TEXT DEFAULT NULL, duration INT DEFAULT NULL, production_year INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A4709120A93351B4 ON patrimony.movie (cnc_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A47091206FED69F9 ON patrimony.movie (arte_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A470912088F1CED7 ON patrimony.movie (canal_vod_id)');
        $this->addSql('CREATE INDEX IDX_A4709120B5B48B91 ON patrimony.movie (public_id)');
        $this->addSql('COMMENT ON COLUMN patrimony.movie.public_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE movie_nationality (movie_id INT NOT NULL, nationality_id UUID NOT NULL, PRIMARY KEY(movie_id, nationality_id))');
        $this->addSql('CREATE INDEX IDX_A54307878F93B6FC ON movie_nationality (movie_id)');
        $this->addSql('CREATE INDEX IDX_A54307871C9DA55 ON movie_nationality (nationality_id)');
        $this->addSql('COMMENT ON COLUMN movie_nationality.nationality_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE movie_genre (movie_id INT NOT NULL, genre_id UUID NOT NULL, PRIMARY KEY(movie_id, genre_id))');
        $this->addSql('CREATE INDEX IDX_FD1229648F93B6FC ON movie_genre (movie_id)');
        $this->addSql('CREATE INDEX IDX_FD1229644296D31F ON movie_genre (genre_id)');
        $this->addSql('COMMENT ON COLUMN movie_genre.genre_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE movie_director (movie_id INT NOT NULL, director_id UUID NOT NULL, PRIMARY KEY(movie_id, director_id))');
        $this->addSql('CREATE INDEX IDX_C266487D8F93B6FC ON movie_director (movie_id)');
        $this->addSql('CREATE INDEX IDX_C266487D899FB366 ON movie_director (director_id)');
        $this->addSql('COMMENT ON COLUMN movie_director.director_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE patrimony.nationality (id UUID NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_203B23C677153098 ON patrimony.nationality (code)');
        $this->addSql('COMMENT ON COLUMN patrimony.nationality.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE patrimony.offer (id UUID NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_90F8E47177153098 ON patrimony.offer (code)');
        $this->addSql('COMMENT ON COLUMN patrimony.offer.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE patrimony.partner (id UUID NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, logo TEXT DEFAULT NULL, condition TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9271E41077153098 ON patrimony.partner (code)');
        $this->addSql('COMMENT ON COLUMN patrimony.partner.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE patrimony.public (id UUID NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DD6E5C7F77153098 ON patrimony.public (code)');
        $this->addSql('COMMENT ON COLUMN patrimony.public.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE patrimony.solution (id UUID NOT NULL, source_movie_id INT DEFAULT NULL, partner_id UUID DEFAULT NULL, offer_id UUID DEFAULT NULL, movie_id INT DEFAULT NULL, internal_partner_id VARCHAR(255) DEFAULT NULL, condition TEXT DEFAULT NULL, link TEXT NOT NULL, start_rights TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, end_rights TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_76F3D634CCB3802E ON patrimony.solution (source_movie_id)');
        $this->addSql('CREATE INDEX IDX_76F3D6349393F8FE ON patrimony.solution (partner_id)');
        $this->addSql('CREATE INDEX IDX_76F3D63453C674EE ON patrimony.solution (offer_id)');
        $this->addSql('CREATE INDEX IDX_76F3D6348F93B6FC ON patrimony.solution (movie_id)');
        $this->addSql('COMMENT ON COLUMN patrimony.solution.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN patrimony.solution.partner_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN patrimony.solution.offer_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE patrimony.source_movie (id INT NOT NULL, partner_id UUID DEFAULT NULL, movie_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, internal_partner_id VARCHAR(255) NOT NULL, has_ad BOOLEAN NOT NULL, poster TEXT DEFAULT NULL, synopsis TEXT DEFAULT NULL, duration INT DEFAULT NULL, production_year INT DEFAULT NULL, nationalities JSON NOT NULL, genres JSON NOT NULL, directors JSON NOT NULL, casting JSON NOT NULL, public VARCHAR(255) DEFAULT NULL, external_ids JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A6FEC1419393F8FE ON patrimony.source_movie (partner_id)');
        $this->addSql('CREATE INDEX IDX_A6FEC1418F93B6FC ON patrimony.source_movie (movie_id)');
        $this->addSql('COMMENT ON COLUMN patrimony.source_movie.partner_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE "patrimony"."user" (id UUID NOT NULL, username VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C524903FF85E0677 ON "patrimony"."user" (username)');
        $this->addSql('COMMENT ON COLUMN "patrimony"."user".id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE patrimony.actormovie ADD CONSTRAINT FK_84D759E98F93B6FC FOREIGN KEY (movie_id) REFERENCES patrimony.movie (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patrimony.actormovie ADD CONSTRAINT FK_84D759E910DAF24A FOREIGN KEY (actor_id) REFERENCES patrimony.actor (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patrimony.movie ADD CONSTRAINT FK_A4709120B5B48B91 FOREIGN KEY (public_id) REFERENCES patrimony.public (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movie_nationality ADD CONSTRAINT FK_A54307878F93B6FC FOREIGN KEY (movie_id) REFERENCES patrimony.movie (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movie_nationality ADD CONSTRAINT FK_A54307871C9DA55 FOREIGN KEY (nationality_id) REFERENCES patrimony.nationality (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movie_genre ADD CONSTRAINT FK_FD1229648F93B6FC FOREIGN KEY (movie_id) REFERENCES patrimony.movie (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movie_genre ADD CONSTRAINT FK_FD1229644296D31F FOREIGN KEY (genre_id) REFERENCES patrimony.genre (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movie_director ADD CONSTRAINT FK_C266487D8F93B6FC FOREIGN KEY (movie_id) REFERENCES patrimony.movie (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE movie_director ADD CONSTRAINT FK_C266487D899FB366 FOREIGN KEY (director_id) REFERENCES patrimony.director (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patrimony.solution ADD CONSTRAINT FK_76F3D634CCB3802E FOREIGN KEY (source_movie_id) REFERENCES patrimony.source_movie (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patrimony.solution ADD CONSTRAINT FK_76F3D6349393F8FE FOREIGN KEY (partner_id) REFERENCES patrimony.partner (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patrimony.solution ADD CONSTRAINT FK_76F3D63453C674EE FOREIGN KEY (offer_id) REFERENCES patrimony.offer (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patrimony.solution ADD CONSTRAINT FK_76F3D6348F93B6FC FOREIGN KEY (movie_id) REFERENCES patrimony.movie (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patrimony.source_movie ADD CONSTRAINT FK_A6FEC1419393F8FE FOREIGN KEY (partner_id) REFERENCES patrimony.partner (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patrimony.source_movie ADD CONSTRAINT FK_A6FEC1418F93B6FC FOREIGN KEY (movie_id) REFERENCES patrimony.movie (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE patrimony.movie_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE patrimony.source_movie_id_seq CASCADE');
        $this->addSql('ALTER TABLE patrimony.actormovie DROP CONSTRAINT FK_84D759E98F93B6FC');
        $this->addSql('ALTER TABLE patrimony.actormovie DROP CONSTRAINT FK_84D759E910DAF24A');
        $this->addSql('ALTER TABLE patrimony.movie DROP CONSTRAINT FK_A4709120B5B48B91');
        $this->addSql('ALTER TABLE movie_nationality DROP CONSTRAINT FK_A54307878F93B6FC');
        $this->addSql('ALTER TABLE movie_nationality DROP CONSTRAINT FK_A54307871C9DA55');
        $this->addSql('ALTER TABLE movie_genre DROP CONSTRAINT FK_FD1229648F93B6FC');
        $this->addSql('ALTER TABLE movie_genre DROP CONSTRAINT FK_FD1229644296D31F');
        $this->addSql('ALTER TABLE movie_director DROP CONSTRAINT FK_C266487D8F93B6FC');
        $this->addSql('ALTER TABLE movie_director DROP CONSTRAINT FK_C266487D899FB366');
        $this->addSql('ALTER TABLE patrimony.solution DROP CONSTRAINT FK_76F3D634CCB3802E');
        $this->addSql('ALTER TABLE patrimony.solution DROP CONSTRAINT FK_76F3D6349393F8FE');
        $this->addSql('ALTER TABLE patrimony.solution DROP CONSTRAINT FK_76F3D63453C674EE');
        $this->addSql('ALTER TABLE patrimony.solution DROP CONSTRAINT FK_76F3D6348F93B6FC');
        $this->addSql('ALTER TABLE patrimony.source_movie DROP CONSTRAINT FK_A6FEC1419393F8FE');
        $this->addSql('ALTER TABLE patrimony.source_movie DROP CONSTRAINT FK_A6FEC1418F93B6FC');
        $this->addSql('DROP TABLE patrimony.actor');
        $this->addSql('DROP TABLE patrimony.actormovie');
        $this->addSql('DROP TABLE patrimony.director');
        $this->addSql('DROP TABLE patrimony.genre');
        $this->addSql('DROP TABLE patrimony.language');
        $this->addSql('DROP TABLE patrimony.movie');
        $this->addSql('DROP TABLE movie_nationality');
        $this->addSql('DROP TABLE movie_genre');
        $this->addSql('DROP TABLE movie_director');
        $this->addSql('DROP TABLE patrimony.nationality');
        $this->addSql('DROP TABLE patrimony.offer');
        $this->addSql('DROP TABLE patrimony.partner');
        $this->addSql('DROP TABLE patrimony.public');
        $this->addSql('DROP TABLE patrimony.solution');
        $this->addSql('DROP TABLE patrimony.source_movie');
        $this->addSql('DROP TABLE "patrimony"."user"');
    }
}
