<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260915160315 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add canceled/canceledAt to contracts, so a contract superseded by the source system before being signed can be pulled out of the signing batch';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contracts ADD canceled TINYINT(1) NOT NULL DEFAULT 0, ADD canceled_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contracts DROP canceled, DROP canceled_at');
    }
}
