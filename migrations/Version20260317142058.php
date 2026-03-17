<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260317142058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, number VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, project_id INT NOT NULL, INDEX IDX_90651744166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE invoice_line (id INT AUTO_INCREMENT NOT NULL, description VARCHAR(255) NOT NULL, quantity INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, invoice_id INT NOT NULL, INDEX IDX_D3D1D6932989F1FD (invoice_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, task VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE ticket (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, priority VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, client_id INT DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_97A0ADA319EB6921 (client_id), INDEX IDX_97A0ADA3A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE ticket_message (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, ticket_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_BA71692D700047D2 (ticket_id), INDEX IDX_BA71692DF675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE invoice_line ADD CONSTRAINT FK_D3D1D6932989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA319EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ticket_message ADD CONSTRAINT FK_BA71692D700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id)');
        $this->addSql('ALTER TABLE ticket_message ADD CONSTRAINT FK_BA71692DF675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_90651744166D1F9C');
        $this->addSql('ALTER TABLE invoice_line DROP FOREIGN KEY FK_D3D1D6932989F1FD');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA319EB6921');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA3A76ED395');
        $this->addSql('ALTER TABLE ticket_message DROP FOREIGN KEY FK_BA71692D700047D2');
        $this->addSql('ALTER TABLE ticket_message DROP FOREIGN KEY FK_BA71692DF675F31B');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE invoice_line');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE ticket');
        $this->addSql('DROP TABLE ticket_message');
    }
}
