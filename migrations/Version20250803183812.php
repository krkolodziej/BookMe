<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250803183812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE booking (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, offer_id INTEGER NOT NULL, service_id INTEGER NOT NULL, employee_id INTEGER DEFAULT NULL, start_time DATETIME NOT NULL, end_time DATETIME NOT NULL, CONSTRAINT FK_E00CEDDEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E00CEDDE8C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E00CEDDE53C674EE FOREIGN KEY (offer_id) REFERENCES offer (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E00CEDDEED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E00CEDDEA76ED395 ON booking (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E00CEDDE8C03F15C ON booking (employee_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E00CEDDE53C674EE ON booking (offer_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E00CEDDEED5CA9E6 ON booking (service_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE employee (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, service_id INTEGER DEFAULT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_5D9F75A1ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5D9F75A1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_5D9F75A1ED5CA9E6 ON employee (service_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_5D9F75A1A76ED395 ON employee (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE notification (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, employee_id INTEGER NOT NULL, message CLOB NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
            , is_read BOOLEAN NOT NULL, CONSTRAINT FK_BF5476CA8C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BF5476CA8C03F15C ON notification (employee_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE offer (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, service_id INTEGER NOT NULL, name VARCHAR(255) NOT NULL, duration INTEGER NOT NULL, price NUMERIC(10, 2) NOT NULL, encoded_name VARCHAR(255) NOT NULL, CONSTRAINT FK_29D6873EED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_29D6873EED5CA9E6 ON offer (service_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE opening_hour (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, service_id INTEGER NOT NULL, day_of_week VARCHAR(20) NOT NULL, opening_time TIME NOT NULL, closing_time TIME NOT NULL, closed BOOLEAN NOT NULL, CONSTRAINT FK_969BD765ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_969BD765ED5CA9E6 ON opening_hour (service_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE opinion (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, booking_id INTEGER DEFAULT NULL, service_id INTEGER NOT NULL, rating INTEGER NOT NULL, content CLOB NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
            , CONSTRAINT FK_AB02B0273301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_AB02B027ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_AB02B0273301C60 ON opinion (booking_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AB02B027ED5CA9E6 ON opinion (service_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE service (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, service_category_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
            , city VARCHAR(100) NOT NULL, street VARCHAR(100) NOT NULL, postal_code VARCHAR(6) NOT NULL, phone_number VARCHAR(20) NOT NULL, encoded_name VARCHAR(255) NOT NULL, image_url VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_E19D9AD2DEDCBB4E FOREIGN KEY (service_category_id) REFERENCES service_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_E19D9AD25E237E06 ON service (name)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E19D9AD2DEDCBB4E ON service (service_category_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE service_category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, image_url VARCHAR(255) DEFAULT NULL, encoded_name VARCHAR(255) NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE service_image (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, service_id INTEGER NOT NULL, url VARCHAR(255) NOT NULL, CONSTRAINT FK_6C4FE9B8ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6C4FE9B8ED5CA9E6 ON service_image (service_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
            , password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, gender VARCHAR(20) NOT NULL, is_admin BOOLEAN NOT NULL, avatar_url VARCHAR(255) DEFAULT NULL, user_type VARCHAR(20) NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE booking
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE employee
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE notification
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE offer
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE opening_hour
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE opinion
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE service
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE service_category
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE service_image
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE users
        SQL);
    }
}
