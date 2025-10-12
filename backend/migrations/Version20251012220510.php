<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251012220510 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE harvest (id SERIAL NOT NULL, hive_id INT NOT NULL, date DATE NOT NULL, weight_g INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_36BDDB37E9A48D12 ON harvest (hive_id)');
        $this->addSql('CREATE TABLE hive (id SERIAL NOT NULL, owner_id INT NOT NULL, name VARCHAR(255) NOT NULL, lat DOUBLE PRECISION NOT NULL, lng DOUBLE PRECISION NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, hive VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DC6DBBF87E3C61F9 ON hive (owner_id)');
        $this->addSql('COMMENT ON COLUMN hive.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE intervention (id SERIAL NOT NULL, hive_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, note TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D11814ABE9A48D12 ON intervention (hive_id)');
        $this->addSql('COMMENT ON COLUMN intervention.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, create_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('COMMENT ON COLUMN "user".create_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE harvest ADD CONSTRAINT FK_36BDDB37E9A48D12 FOREIGN KEY (hive_id) REFERENCES hive (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE hive ADD CONSTRAINT FK_DC6DBBF87E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814ABE9A48D12 FOREIGN KEY (hive_id) REFERENCES hive (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE harvest DROP CONSTRAINT FK_36BDDB37E9A48D12');
        $this->addSql('ALTER TABLE hive DROP CONSTRAINT FK_DC6DBBF87E3C61F9');
        $this->addSql('ALTER TABLE intervention DROP CONSTRAINT FK_D11814ABE9A48D12');
        $this->addSql('DROP TABLE harvest');
        $this->addSql('DROP TABLE hive');
        $this->addSql('DROP TABLE intervention');
        $this->addSql('DROP TABLE "user"');
    }
}
