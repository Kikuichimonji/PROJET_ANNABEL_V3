<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260708071829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Retire la table consult_calendar (fonctionnalite agenda/calendrier retiree, jamais utilisee)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE consult_calendar');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE consult_calendar (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) DEFAULT NULL COLLATE "BINARY", start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL)');
    }
}
