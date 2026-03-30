<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260327145130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quote CHANGE created_at created_at DATETIME NOT NULL, CHANGE valid_until valid_until DATETIME NOT NULL');
        $this->addSql('ALTER TABLE quote RENAME INDEX uniq_6b71cbf45a8ca47 TO UNIQ_6B71CBF4AC28B117');
        $this->addSql('ALTER TABLE quote_line RENAME INDEX idx_a62ddf48db805178 TO IDX_43F3EB7CDB805178');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quote CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE valid_until valid_until DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE quote RENAME INDEX uniq_6b71cbf4ac28b117 TO UNIQ_6B71CBF45A8CA47');
        $this->addSql('ALTER TABLE quote_line RENAME INDEX idx_43f3eb7cdb805178 TO IDX_A62DDF48DB805178');
    }
}
