<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240614125211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contract_signature (id INT AUTO_INCREMENT NOT NULL, contract_id INT NOT NULL, signature LONGTEXT NOT NULL, link LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_831F59D72576E0FD (contract_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contract_signature ADD CONSTRAINT FK_831F59D72576E0FD FOREIGN KEY (contract_id) REFERENCES contracts (id)');
        $this->addSql('ALTER TABLE contracts ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP  COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contract_signature DROP FOREIGN KEY FK_831F59D72576E0FD');
        $this->addSql('DROP TABLE contract_signature');
        $this->addSql('ALTER TABLE contracts DROP created_at');
    }
}
