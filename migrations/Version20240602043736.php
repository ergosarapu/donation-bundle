<?php

declare(strict_types=1);

namespace DonationBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240602043736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(128) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, given_name VARCHAR(255) NOT NULL, family_name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE payment CHANGE number number VARCHAR(255) DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE client_email client_email VARCHAR(255) DEFAULT NULL, CHANGE client_id client_id VARCHAR(255) DEFAULT NULL, CHANGE currency_code currency_code VARCHAR(255) DEFAULT NULL, CHANGE details details JSON NOT NULL');
        $this->addSql('ALTER TABLE payment_token CHANGE details details LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:object)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE `user`');
        $this->addSql('ALTER TABLE payment CHANGE number number VARCHAR(255) DEFAULT \'NULL\', CHANGE description description VARCHAR(255) DEFAULT \'NULL\', CHANGE client_email client_email VARCHAR(255) DEFAULT \'NULL\', CHANGE client_id client_id VARCHAR(255) DEFAULT \'NULL\', CHANGE currency_code currency_code VARCHAR(255) DEFAULT \'NULL\', CHANGE details details LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE payment_token CHANGE details details LONGTEXT DEFAULT \'NULL\' COMMENT \'(DC2Type:object)\'');
    }
}
