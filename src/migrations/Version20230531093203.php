<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ramsey\Uuid\Uuid;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230531093203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE account_role_right (id INT AUTO_INCREMENT NOT NULL, account_id INT NOT NULL, role_id INT NOT NULL, rights_id INT NOT NULL, INDEX IDX_BF8D6F9F9B6B5FBA (account_id), INDEX IDX_BF8D6F9FD60322AC (role_id), INDEX IDX_BF8D6F9FB196EE6E (rights_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `right` (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, position INT DEFAULT NULL, code VARCHAR(255) NOT NULL, slug_name VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_B4CA7514D17F50A6 (uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, slug_name VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_57698A6AD17F50A6 (uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_account_role (id INT AUTO_INCREMENT NOT NULL, account_id INT NOT NULL, role_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_5B3671C29B6B5FBA (account_id), INDEX IDX_5B3671C2D60322AC (role_id), INDEX IDX_5B3671C2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE account_role_right ADD CONSTRAINT FK_BF8D6F9F9B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE account_role_right ADD CONSTRAINT FK_BF8D6F9FD60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE account_role_right ADD CONSTRAINT FK_BF8D6F9FB196EE6E FOREIGN KEY (rights_id) REFERENCES `right` (id)');
        $this->addSql('ALTER TABLE user_account_role ADD CONSTRAINT FK_5B3671C29B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE user_account_role ADD CONSTRAINT FK_5B3671C2D60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE user_account_role ADD CONSTRAINT FK_5B3671C2A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user_account_role ADD CONSTRAINT unique_account_user_role UNIQUE (account_id, user_id)');
        $this->addSql('ALTER TABLE account_role_right ADD CONSTRAINT unique_account_role_right UNIQUE (account_id,role_id, rights_id)');
        $this->addSql('ALTER TABLE user CHANGE firstname first_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE lastname last_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE contact ADD first_name VARCHAR(255) DEFAULT NULL, ADD last_name VARCHAR(255) DEFAULT NULL, DROP firstname, DROP lastname');
        $this->addSql('ALTER TABLE account ADD max_invitations VARCHAR(255) DEFAULT 1');
        $this->addSql('ALTER TABLE account ADD logo VARCHAR(255) DEFAULT NULL');
        // $this->addSql('ALTER TABLE folder ADD is_in_trash BOOLEAN DEFAULT 0');
        // $this->addSql('ALTER TABLE video ADD is_in_trash BOOLEAN DEFAULT 0');
        $this->addSql('UPDATE account SET logo =
                         (SELECT user.logo FROM user WHERE account.id = user.account_id AND user.roles NOT LIKE \'%ROLE_USER%\') WHERE account.id IN
                         (SELECT user.account_id FROM user WHERE account.id = user.account_id  AND user.roles NOT LIKE \'%ROLE_USER%\') ');
        $this->addSql('ALTER TABLE  user drop logo');
        $this->addSql('INSERT INTO `role` (`uuid`, `name`, `code`) VALUES (:uuid, :name, :code)', [
            'uuid' => Uuid::uuid4()->toString(),
            'name' => 'admin',
            'code' => 'admin',
        ]);
        $this->addSql('INSERT INTO `role` (`uuid`, `name`, `code`) VALUES (:uuid, :name, :code)', [
            'uuid' => Uuid::uuid4()->toString(),
            'name' => 'editeur',
            'code' => 'editor',
        ]);
        $this->addSql('INSERT INTO `role` (`uuid`, `name`, `code`) VALUES (:uuid, :name, :code)', [
            'uuid' => Uuid::uuid4()->toString(),
            'name' => 'lecteur',
            'code' => 'reader',
        ]);


        $this->addSql(
            'INSERT INTO `right` (`uuid`,`name`,`code`) VALUES (:uuid,:name,:code) ',
            [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'dashboard',
                'code' => 'dashboard',
            ]
        );
        $this->addSql(
            'INSERT INTO `right` (`uuid`,`name`,`code`) VALUES (:uuid,:name,:code)',
            [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'profile',
                'code' => 'profile',
            ]
        );
        $this->addSql(
            'INSERT INTO `right` (`uuid`,`name`,`code`) VALUES(:uuid,:name,:code)',
            [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'librarie de videos',
                'code' => 'video_library',
            ]
        );
        $this->addSql(
            'INSERT INTO `right` (`uuid`,`name`,`code`) VALUES (:uuid,:name,:code)',
            [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'detail video',
                'code' => 'video_detail',
            ]
        );
        $this->addSql(
            'INSERT INTO `right` (`uuid`,`name`,`code`) VALUES (:uuid,:name,:code)',
            [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'lire une video',
                'code' => 'video_stream',
            ]
        );
        $this->addSql(
            'INSERT INTO `right` (`uuid`,`name`,`code`) VALUES (:uuid,:name,:code)',
            [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'telecharger video',
                'code' => 'video_download',
            ]
        );
        $this->addSql(
            'INSERT INTO `right` (`uuid`,`name`,`code`) VALUES (:uuid,:name,:code)',
            [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'supprimer video',
                'code' => 'video_delete',
            ]
        );
        $this->addSql(
            'INSERT INTO `right` (`uuid`,`name`,`code`) VALUES (:uuid,:name,:code)',
            [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'encoder video',
                'code' => 'video_encode',
            ]
        );
        $this->addSql(
            'INSERT INTO `right` (`uuid`,`name`,`code`) VALUES (:uuid,:name,:code)',
            [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 're-encoder video',
                'code' => 'video_recode',
            ]
        );
        $this->addSql(
            'INSERT INTO `right` (`uuid`,`name`,`code`) VALUES (:uuid,:name,:code)',
            [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'inviter une personne',
                'code' => 'account_invite',
            ]
        );
        $this->addSql(
            'INSERT INTO `right` (`uuid`,`name`,`code`) VALUES (:uuid,:name,:code)',
            [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'rapport d\'encodage',
                'code' => 'report_encode',
            ]
        );
        $this->addSql(
            'INSERT INTO `right` (`uuid`,`name`,`code`) VALUES (:uuid,:name,:code)',
            [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'config rapport encodage',
                'code' => 'report_config',
            ]
        );
        $this->addSql(
            'INSERT INTO `right` (`uuid`,`name`,`code`) VALUES (:uuid,:name,:code)',
            [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'facturation',
                'code' => 'account_invoice',
            ]
        );
        $this->addSql(
            'INSERT INTO `right` (`uuid`,`name`,`code`) VALUES (:uuid,:name,:code)',
            [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => 'paiement',
                'code' => 'account_payment',
            ]
        );

        //         $this->addSql('INSERT INTO `account_role_right` (`account_id`,`role_id`,`right_id`)
        //         SELECT a.id,r.id,rg.id
        //         FROM `account` a ,`role` r ,`rights` rg
        //         WHERE a.id = u.account_id
        // ');
        $this->addSql('INSERT INTO `user_account_role` (`account_id`,`role_id`,`user_id`)
            SELECT  a.id,  r.id,u.id
            FROM `account` a ,`role` r ,`user` u
            WHERE a.id = u.account_id AND u.roles LIKE \'%ROLE_DEV%\' AND r.code= \'admin\'
            OR a.id = u.account_id AND u.roles LIKE \'%ROLE_VIDMIZER%\' AND r.code= \'admin\'
            OR a.id = u.account_id AND u.roles LIKE \'%ROLE_PILOTE%\' AND r.code= \'admin\'
            OR a.id = u.account_id AND u.roles LIKE \'%ROLE_USER%\' AND r.code= \'reader\'
        ');


        $this->addSql('UPDATE `account` set max_invitations = 3 WHERE is_multi_account = true');
        $this->addSql('UPDATE `user` set roles = "[\"ROLE_USER\"]" WHERE user.roles LIKE \'%ROLE_PILOTE%\'');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6499B6B5FBA');
        $this->addSql('DROP INDEX IDX_8D93D6499B6B5FBA ON user');
        $this->addSql('ALTER TABLE user DROP account_id');
        $this->addSql('CREATE TABLE folder (id INT AUTO_INCREMENT NOT NULL,uuid VARCHAR(255) DEFAULT NULL , tree_root INT DEFAULT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, lft INT NOT NULL, rgt INT NOT NULL, level INT NOT NULL, INDEX IDX_ECA209CDA977936C (tree_root), INDEX IDX_ECA209CD727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE folder ADD CONSTRAINT FK_ECA209CDA977936C FOREIGN KEY (tree_root) REFERENCES folder (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE folder ADD CONSTRAINT FK_ECA209CD727ACA70 FOREIGN KEY (parent_id) REFERENCES folder (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE folder ADD account_id INT DEFAULT NULL, ADD created_by VARCHAR(255) DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD is_archived TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE folder ADD CONSTRAINT FK_ECA209CD9B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('CREATE INDEX IDX_ECA209CD9B6B5FBA ON folder (account_id)');
        $this->addSql('ALTER TABLE video ADD folder_id INT DEFAULT NULL, CHANGE media_type media_type ENUM(\'DEFAULT\', \'WEBINAR\', \'FIXED_SHOT\', \'HIGH_RESOLUTION\',\'GREEN++\',\'ANIMATION\',\'STILL_IMAGE\',\'TWITCH\',\'GREEN+\'), CHANGE encoding_state encoding_state ENUM( \'PENDING\',\'ANALYSING\',\'RETRY\', \'ENCODING\', \'ENCODED\',\'ERROR\')');
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2C162CB942 FOREIGN KEY (folder_id) REFERENCES folder (id)');
        $this->addSql('CREATE INDEX IDX_7CC7DA2C162CB942 ON video (folder_id)');
        $this->addSql('CREATE TABLE user_folder_role (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, folder_id INT DEFAULT NULL, role_id INT DEFAULT NULL, INDEX IDX_E7ED65EAA76ED395 (user_id), INDEX IDX_E7ED65EA162CB942 (folder_id), INDEX IDX_E7ED65EAD60322AC (role_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_folder_role ADD CONSTRAINT FK_E7ED65EAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user_folder_role ADD CONSTRAINT FK_E7ED65EA162CB942 FOREIGN KEY (folder_id) REFERENCES folder (id)');
        $this->addSql('ALTER TABLE user_folder_role ADD CONSTRAINT FK_E7ED65EAD60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE user_folder_role ADD CONSTRAINT unique_user_folder_role UNIQUE (user_id, folder_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account_role_right DROP FOREIGN KEY FK_BF8D6F9F9B6B5FBA');
        $this->addSql('ALTER TABLE account_role_right DROP FOREIGN KEY FK_BF8D6F9FD60322AC');
        $this->addSql('ALTER TABLE account_role_right DROP FOREIGN KEY FK_BF8D6F9FB196EE6E');
        $this->addSql('ALTER TABLE user_account_role DROP FOREIGN KEY FK_5B3671C29B6B5FBA');
        $this->addSql('ALTER TABLE user_account_role DROP FOREIGN KEY FK_5B3671C2D60322AC');
        $this->addSql('ALTER TABLE user_account_role DROP FOREIGN KEY FK_5B3671C2A76ED395');
        $this->addSql('DROP TABLE account_role_right');
        $this->addSql('DROP TABLE `right`');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE user_account_role');
    }
}
