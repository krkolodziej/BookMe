<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250817155628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unique constraint from service.name column';
    }

    public function up(Schema $schema): void
    {
        // Remove unique constraint from service.name
        $this->addSql('DROP INDEX UNIQ_E19D9AD25E237E06 ON service');
    }

    public function down(Schema $schema): void
    {
        // Add back unique constraint on service.name
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E19D9AD25E237E06 ON service (name)');
    }
}
