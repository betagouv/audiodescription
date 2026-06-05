<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260605125021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audiodescribed_poster column to movie table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE patrimony.movie ADD audiodescribed_poster TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE patrimony.movie DROP COLUMN audiodescribed_poster');
    }
}
