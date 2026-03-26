<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260323110344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client ADD user_account_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C74404553C0C9956 FOREIGN KEY (user_account_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C74404553C0C9956 ON client (user_account_id)');
        $this->addSql('ALTER TABLE user ADD invitation_token VARCHAR(255) DEFAULT NULL, ADD invitation_token_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C74404553C0C9956');
        $this->addSql('DROP INDEX UNIQ_C74404553C0C9956 ON client');
        $this->addSql('ALTER TABLE client DROP user_account_id');
        $this->addSql('ALTER TABLE user DROP invitation_token, DROP invitation_token_expires_at');
    }
}
