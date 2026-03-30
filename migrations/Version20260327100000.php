<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter les tables Quote et QuoteLine
 */
final class Version20260327100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables quote et quote_line pour la gestion des devis';
    }

    public function up(Schema $schema): void
    {
        // Table Quote
        $this->addSql('CREATE TABLE quote (
            id INT AUTO_INCREMENT NOT NULL,
            client_id INT NOT NULL,
            user_id INT NOT NULL,
            quote_number VARCHAR(50) NOT NULL,
            status VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            valid_until DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            notes LONGTEXT DEFAULT NULL,
            subject VARCHAR(255) DEFAULT NULL,
            UNIQUE INDEX UNIQ_6B71CBF45A8CA47 (quote_number),
            INDEX IDX_6B71CBF419EB6921 (client_id),
            INDEX IDX_6B71CBF4A76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table QuoteLine
        $this->addSql('CREATE TABLE quote_line (
            id INT AUTO_INCREMENT NOT NULL,
            quote_id INT NOT NULL,
            description VARCHAR(255) NOT NULL,
            quantity NUMERIC(10, 2) NOT NULL,
            unit_price NUMERIC(10, 2) NOT NULL,
            position INT DEFAULT NULL,
            INDEX IDX_A62DDF48DB805178 (quote_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Foreign keys
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF419EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE quote_line ADD CONSTRAINT FK_A62DDF48DB805178 FOREIGN KEY (quote_id) REFERENCES quote (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quote_line DROP FOREIGN KEY FK_A62DDF48DB805178');
        $this->addSql('ALTER TABLE quote DROP FOREIGN KEY FK_6B71CBF419EB6921');
        $this->addSql('ALTER TABLE quote DROP FOREIGN KEY FK_6B71CBF4A76ED395');
        $this->addSql('DROP TABLE quote_line');
        $this->addSql('DROP TABLE quote');
    }
}
