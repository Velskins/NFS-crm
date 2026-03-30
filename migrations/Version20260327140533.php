<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260327140533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quote DROP FOREIGN KEY `FK_6B71CBF419EB6921`');
        $this->addSql('ALTER TABLE quote DROP FOREIGN KEY `FK_6B71CBF4A76ED395`');
        $this->addSql('ALTER TABLE quote_line DROP FOREIGN KEY `FK_A62DDF48DB805178`');
        $this->addSql('DROP TABLE quote');
        $this->addSql('DROP TABLE quote_line');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE quote (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, user_id INT NOT NULL, quote_number VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, status VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', valid_until DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, subject VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_6B71CBF419EB6921 (client_id), INDEX IDX_6B71CBF4A76ED395 (user_id), UNIQUE INDEX UNIQ_6B71CBF45A8CA47 (quote_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE quote_line (id INT AUTO_INCREMENT NOT NULL, quote_id INT NOT NULL, description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, quantity NUMERIC(10, 2) NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, position INT DEFAULT NULL, INDEX IDX_A62DDF48DB805178 (quote_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT `FK_6B71CBF419EB6921` FOREIGN KEY (client_id) REFERENCES client (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT `FK_6B71CBF4A76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE quote_line ADD CONSTRAINT `FK_A62DDF48DB805178` FOREIGN KEY (quote_id) REFERENCES quote (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
