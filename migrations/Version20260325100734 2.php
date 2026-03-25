<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260325100734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD company_name VARCHAR(255) DEFAULT NULL, ADD company_address VARCHAR(255) DEFAULT NULL, ADD company_postal_code VARCHAR(10) DEFAULT NULL, ADD company_city VARCHAR(255) DEFAULT NULL, ADD siret VARCHAR(50) DEFAULT NULL, ADD tva_number VARCHAR(50) DEFAULT NULL, ADD phone VARCHAR(45) DEFAULT NULL, ADD notif_echeance TINYINT NOT NULL, ADD notif_new_project TINYINT NOT NULL, ADD notif_document_uploaded TINYINT NOT NULL, ADD notif_payment_received TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP company_name, DROP company_address, DROP company_postal_code, DROP company_city, DROP siret, DROP tva_number, DROP phone, DROP notif_echeance, DROP notif_new_project, DROP notif_document_uploaded, DROP notif_payment_received');
    }
}
