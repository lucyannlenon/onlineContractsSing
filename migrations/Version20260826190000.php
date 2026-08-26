<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Prevent duplicate signatures for the same contract document (unique contract_id + name)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contract_signature ADD CONSTRAINT uniq_contract_signature_contract_name UNIQUE (contract_id, name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contract_signature DROP INDEX uniq_contract_signature_contract_name');
    }
}
