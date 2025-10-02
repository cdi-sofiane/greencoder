<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ramsey\Uuid\Uuid;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220920114830 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {


        $this->addSql('CREATE TABLE account (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(255) DEFAULT NULL ,
         email VARCHAR(180) NOT NULL, name VARCHAR(255) DEFAULT NULL, company VARCHAR(255) DEFAULT NULL,
         address VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(255) DEFAULT NULL, country VARCHAR(255) DEFAULT NULL,
         usages ENUM(\'Individual\', \'Professional\'), tva VARCHAR(255) DEFAULT NULL, siret VARCHAR(255) DEFAULT NULL, 
         api_key TEXT(65535) DEFAULT NULL, credit_encode BIGINT NULL, credit_storage BIGINT DEFAULT NULL, 
         is_multi_account TINYINT(1) NOT NULL, is_active TINYINT(1) NOT NULL,
         created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', 
         UNIQUE INDEX UNIQ_7D3656A4D17F50A6 (uuid), UNIQUE INDEX UNIQ_7D3656A4E7927C74 (email),
         PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('INSERT INTO  `account`(email, name, company, address , postal_code , country, usages ,tva,siret,api_key,credit_encode,credit_storage,uuid,is_multi_account,is_active,created_at) 
        SELECT (email),(email),(company),(address),(postal_code),(country),(usages),(tva),(siret),(api_key),(credit_encode),(credit_storage),(UUID()),(true),(true),(created_at)  FROM user  ');


        $this->addSql('ALTER TABLE report ADD account_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F77849B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');

        $this->addSql('ALTER TABLE report_config ADD account_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE report_config ADD CONSTRAINT FK_FD79FAE39B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE account ADD report_config_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE account ADD CONSTRAINT FK_7D3656A4A6F6B9B FOREIGN KEY (report_config_id) REFERENCES report_config (id)');


        $this->addSql('UPDATE  account  set is_multi_account = false     ');

        $this->addSql('ALTER TABLE consumption CHANGE launched launched ENUM(\'download\', \'stream\')');
        $this->addSql('ALTER TABLE forfait CHANGE type type ENUM( \'Gratuit\',\'OneShot\', \'Credit\', \'Abonnement\')');
        $this->addSql('ALTER TABLE user ADD account_id INT DEFAULT NULL, ADD is_delete TINYINT(1) NOT NULL,DROP api_key, DROP company, DROP address, DROP postal_code, DROP country, DROP usages, DROP tva, DROP siret,DROP credit_encode,DROP credit_storage');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6499B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');


        $this->addSql('UPDATE  user  set account_id =  (SELECT account.id  from account where user.email = account.email) ');
        $this->addSql('UPDATE  user  set roles = \'["ROLE_PILOTE"]\'  where user.roles like \'%ROLE_USER%\'   ');
        $this->addSql('ALTER TABLE `order` ADD account_id INT DEFAULT NULL');
        $this->addSql('UPDATE  `order`  set account_id =  ( SELECT account.id  from account where order.user_id = (select user.id from user where account_id = account.id))');
        $this->addSql('ALTER TABLE tags ADD account_id INT DEFAULT NULL');
        $this->addSql('UPDATE  `tags`  set account_id =  ( SELECT account.id  from account where tags.user_id = (select user.id from user where account_id = account.id))');
        $this->addSql('ALTER TABLE tags ADD CONSTRAINT FK_6FBC94269B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('CREATE INDEX IDX_6FBC94269B6B5FBA ON tags (account_id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993989B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('CREATE INDEX IDX_F52993989B6B5FBA ON `order` (account_id)');

        $this->addSql('UPDATE  account  set usages = \'Individual\'  where account.email = (select user.email from user WHERE user.email = account.email and user.roles   NOT LIKE \'%ROLE_PILOTE%\')   ');

        $this->addSql('CREATE INDEX IDX_8D93D6499B6B5FBA ON user (account_id)');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('ALTER TABLE tags DROP FOREIGN KEY FK_6FBC9426A76ED395');
        $this->addSql('DROP INDEX IDX_6FBC9426A76ED395 ON tags');
        $this->addSql('ALTER TABLE `tags` DROP user_id ');
        $this->addSql('DROP INDEX IDX_F5299398A76ED395 ON `order`');
        $this->addSql('ALTER TABLE `order` DROP user_id ');
        $this->addSql('ALTER TABLE video ADD account_id INT DEFAULT NULL, ADD encoded_by VARCHAR(255) DEFAULT NULL,CHANGE media_type media_type ENUM(\'DEFAULT\', \'WEBINAR\', \'FIXED_SHOT\', \'HIGH_RESOLUTION\',\'GREEN++\',\'ANIMATION\',\'STILL_IMAGE\'), CHANGE encoding_state encoding_state ENUM( \'ANALYSING\',\'RETRY\', \'ENCODING\', \'ENCODED\',\'ERROR\')');
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2C9B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('UPDATE  video  set account_id = ( SELECT account.id  from account where video.user_id = (select user.id from user where account_id = account.id))  ');
        $this->addSql('UPDATE video SET encoded_by = (SELECT user.email FROM user WHERE video.user_id = user.id and  video.encoded_by is NULL ) ');

        $this->addSql('UPDATE account set report_config_id = (
            SELECT report_config.id FROM report_config WHERE report_config.user_id = (
                SELECT user.id FROM user WHERE user.account_id = account.id
                )) ');
        $this->addSql('UPDATE report_config set report_config.account_id = (
            SELECT user.account_id from user WHERE report_config.user_id = user.id
            ) ');
        $this->addSql('UPDATE report set report.account_id = (
        SELECT user.account_id from user WHERE report.user_id = user.id
        ) ');
        $this->addSql('ALTER TABLE report_config DROP FOREIGN KEY FK_FD79FAE3A76ED395');
        $this->addSql('DROP INDEX UNIQ_FD79FAE3A76ED395 ON report_config');
        $this->addSql('ALTER TABLE `report_config` DROP user_id ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993989B6B5FBA');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6499B6B5FBA');
        $this->addSql('ALTER TABLE `video` DROP FOREIGN KEY FK_7CC7DA2C9B6B5FBA');
        $this->addSql('CREATE TABLE band_width (id INT AUTO_INCREMENT NOT NULL, encode_id INT NOT NULL, uuid VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_70F8FBB38F2A43A5 (encode_id), UNIQUE INDEX UNIQ_70F8FBB3D17F50A6 (uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE band_width ADD CONSTRAINT FK_70F8FBB38F2A43A5 FOREIGN KEY (encode_id) REFERENCES encode (id)');
        $this->addSql('DROP TABLE account');
        $this->addSql('ALTER TABLE consumption CHANGE launched launched VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE `forfait` CHANGE type type VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('DROP INDEX IDX_F52993989B6B5FBA ON `order`');
        $this->addSql('ALTER TABLE `order` CHANGE account_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_F5299398A76ED395 ON `order` (user_id)');
        $this->addSql('DROP INDEX IDX_8D93D6499B6B5FBA ON `user`');
        $this->addSql('ALTER TABLE `user` ADD api_key LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD company VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD address VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD postal_code VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD country VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD usages VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD tva VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD siret VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD credit_encode BIGINT DEFAULT NULL, ADD credit_storage BIGINT DEFAULT NULL, DROP account_id');
        $this->addSql('DROP INDEX IDX_7CC7DA2C9B6B5FBA ON `video`');
        $this->addSql('ALTER TABLE `video` DROP account_id, CHANGE media_type media_type VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE encoding_state encoding_state VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
