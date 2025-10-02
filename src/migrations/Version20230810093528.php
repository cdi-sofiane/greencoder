<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230810093528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE folder ADD is_in_trash TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE video ADD is_in_trash TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account CHANGE usages usages VARCHAR(255) DEFAULT NULL, CHANGE max_invitations max_invitations VARCHAR(255) DEFAULT \'1\'');
        $this->addSql('CREATE UNIQUE INDEX unique_account_role_right ON account_role_right (account_id, role_id, rights_id)');
        $this->addSql('ALTER TABLE consumption CHANGE launched launched VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_ECA209CDD17F50A6 ON folder');
        $this->addSql('ALTER TABLE folder DROP is_in_trash, CHANGE uuid uuid VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `forfait` CHANGE type type VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX unique_account_user_role ON user_account_role (account_id, user_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_user_folder_role ON user_folder_role (user_id, folder_id)');
        $this->addSql('ALTER TABLE `video` DROP is_in_trash, CHANGE media_type media_type VARCHAR(255) DEFAULT NULL, CHANGE encoding_state encoding_state VARCHAR(255) DEFAULT NULL');
    }
}
