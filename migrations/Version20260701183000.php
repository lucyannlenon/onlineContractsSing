<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add legal evidence payload to contract signatures';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contract_signature ADD evidence JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contract_signature DROP evidence');
    }
}
