<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221117131012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account CHANGE usages usages ENUM(\'Individual\', \'Professional\')');
        $this->addSql('ALTER TABLE consumption CHANGE launched launched ENUM(\'download\', \'stream\')');
        $this->addSql('ALTER TABLE forfait CHANGE type type ENUM( \'Gratuit\',\'OneShot\', \'Credit\', \'Abonnement\')');
        $this->addSql('ALTER TABLE video CHANGE media_type media_type ENUM(\'DEFAULT\', \'WEBINAR\', \'FIXED_SHOT\', \'HIGH_RESOLUTION\',\'GREEN++\',\'ANIMATION\',\'STILL_IMAGE\'), CHANGE encoding_state encoding_state ENUM( \'PENDING\',\'ANALYSING\',\'RETRY\', \'ENCODING\', \'ENCODED\',\'ERROR\')');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `forfait` CHANGE type type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `video` CHANGE media_type media_type VARCHAR(255) DEFAULT NULL, CHANGE encoding_state encoding_state VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE consumption CHANGE launched launched VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE account CHANGE usages usages VARCHAR(255) DEFAULT NULL');
    }
}
