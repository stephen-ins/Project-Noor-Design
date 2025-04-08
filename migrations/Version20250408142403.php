<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250408142403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE order_details (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL, prix NUMERIC(10, 2) NOT NULL, INDEX IDX_845CA2C18D9F6D38 (order_id), INDEX IDX_845CA2C14584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE order_details ADD CONSTRAINT FK_845CA2C18D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE order_details ADD CONSTRAINT FK_845CA2C14584665A FOREIGN KEY (product_id) REFERENCES products (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE order_details DROP FOREIGN KEY FK_845CA2C18D9F6D38
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE order_details DROP FOREIGN KEY FK_845CA2C14584665A
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE order_details
        SQL);
    }
}
