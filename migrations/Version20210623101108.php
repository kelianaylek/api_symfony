<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210623101108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment DROP CONSTRAINT IF EXISTS FK_9474526CF675F31B');
        $this->addSql('ALTER TABLE event DROP CONSTRAINT IF EXISTS FK_3BAE0AA77E3C61F9');
        $this->addSql('ALTER TABLE event_user DROP CONSTRAINT IF EXISTS FK_92589AE2A76ED395');
        $this->addSql('ALTER TABLE group_user DROP CONSTRAINT IF EXISTS FK_A4C98D39A76ED395');
        $this->addSql('ALTER TABLE group_admins DROP CONSTRAINT IF EXISTS FK_7166CDDFA76ED395');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT IF EXISTS FK_B6BD307FF675F31B');
        $this->addSql('ALTER TABLE poll_choice_user DROP CONSTRAINT IF EXISTS FK_EA6E1E68A76ED395');
        $this->addSql('ALTER TABLE post DROP CONSTRAINT IF EXISTS FK_5A8A6C8DF675F31B');
        $this->addSql('ALTER TABLE post_likes DROP CONSTRAINT IF EXISTS FK_DED1C292A76ED395');
        $this->addSql('ALTER TABLE event_user DROP CONSTRAINT IF EXISTS FK_92589AE271F7E88B');
        $this->addSql('ALTER TABLE post DROP CONSTRAINT IF EXISTS FK_5A8A6C8D71F7E88B');
        $this->addSql('ALTER TABLE group_user DROP CONSTRAINT IF EXISTS FK_A4C98D39FE54D947');
        $this->addSql('ALTER TABLE group_admins DROP CONSTRAINT IF EXISTS FK_7166CDDFFE54D947');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT IF EXISTS FK_B6BD307FB9ADA51B');
        $this->addSql('ALTER TABLE poll_choice DROP CONSTRAINT IF EXISTS FK_2DAE19C93C947C0F');
        $this->addSql('ALTER TABLE poll_choice_user DROP CONSTRAINT IF EXISTS FK_EA6E1E6852514F25');
        $this->addSql('ALTER TABLE comment DROP CONSTRAINT IF EXISTS FK_9474526C4B89032C');
        $this->addSql('ALTER TABLE poll DROP CONSTRAINT IF EXISTS FK_84BCFA454B89032C');
        $this->addSql('ALTER TABLE post_likes DROP CONSTRAINT IF EXISTS FK_DED1C2924B89032C');
        $this->addSql('DROP SEQUENCE IF EXISTS app_user_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE IF EXISTS comment_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE IF EXISTS event_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE IF EXISTS "group_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE IF EXISTS message_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE IF EXISTS poll_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE IF EXISTS poll_choice_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE IF EXISTS post_id_seq CASCADE');
        $this->addSql('DROP TABLE IF EXISTS app_user');
        $this->addSql('DROP TABLE IF EXISTS comment');
        $this->addSql('DROP TABLE IF EXISTS event');
        $this->addSql('DROP TABLE IF EXISTS event_user');
        $this->addSql('DROP TABLE IF EXISTS "group"');
        $this->addSql('DROP TABLE IF EXISTS group_user');
        $this->addSql('DROP TABLE IF EXISTS group_admins');
        $this->addSql('DROP TABLE IF EXISTS message');
        $this->addSql('DROP TABLE IF EXISTS poll');
        $this->addSql('DROP TABLE IF EXISTS poll_choice');
        $this->addSql('DROP TABLE IF EXISTS poll_choice_user');
        $this->addSql('DROP TABLE IF EXISTS post');
        $this->addSql('DROP TABLE IF EXISTS post_likes');

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS app_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS comment_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS event_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS "group_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS message_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS poll_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS poll_choice_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS post_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE IF NOT EXISTS app_user  (id INT NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_88BDF3E9E7927C74 ON app_user (email)');
        $this->addSql('CREATE TABLE IF NOT EXISTS comment (id INT NOT NULL, author_id INT DEFAULT NULL, post_id INT DEFAULT NULL, message TEXT NOT NULL, published_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, image VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_9474526CF675F31B ON comment (author_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_9474526C4B89032C ON comment (post_id)');
        $this->addSql('COMMENT ON COLUMN comment.published_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE IF NOT EXISTS event (id INT NOT NULL, owner_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_3BAE0AA77E3C61F9 ON event (owner_id)');
        $this->addSql('CREATE TABLE IF NOT EXISTS event_user (event_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(event_id, user_id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_92589AE271F7E88B ON event_user (event_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_92589AE2A76ED395 ON event_user (user_id)');
        $this->addSql('CREATE TABLE IF NOT EXISTS "group" (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE IF NOT EXISTS group_user (group_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(group_id, user_id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_A4C98D39FE54D947 ON group_user (group_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_A4C98D39A76ED395 ON group_user (user_id)');
        $this->addSql('CREATE TABLE IF NOT EXISTS group_admins (group_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(group_id, user_id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_7166CDDFFE54D947 ON group_admins (group_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_7166CDDFA76ED395 ON group_admins (user_id)');
        $this->addSql('CREATE TABLE IF NOT EXISTS message (id INT NOT NULL, author_id INT NOT NULL, in_group_id INT NOT NULL, content VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_B6BD307FF675F31B ON message (author_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_B6BD307FB9ADA51B ON message (in_group_id)');
        $this->addSql('CREATE TABLE IF NOT EXISTS poll (id INT NOT NULL, post_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_84BCFA454B89032C ON poll (post_id)');
        $this->addSql('CREATE TABLE IF NOT EXISTS poll_choice (id INT NOT NULL, poll_id INT NOT NULL, title VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_2DAE19C93C947C0F ON poll_choice (poll_id)');
        $this->addSql('CREATE TABLE IF NOT EXISTS poll_choice_user (poll_choice_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(poll_choice_id, user_id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_EA6E1E6852514F25 ON poll_choice_user (poll_choice_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_EA6E1E68A76ED395 ON poll_choice_user (user_id)');
        $this->addSql('CREATE TABLE IF NOT EXISTS post (id INT NOT NULL, author_id INT DEFAULT NULL, event_id INT DEFAULT NULL, content TEXT NOT NULL, published_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, image VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_5A8A6C8DF675F31B ON post (author_id)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_5A8A6C8D71F7E88B ON post (event_id)');
        $this->addSql('COMMENT ON COLUMN post.published_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE post_likes (post_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(post_id, user_id))');
        $this->addSql('CREATE INDEX IDX_DED1C2924B89032C ON post_likes (post_id)');
        $this->addSql('CREATE INDEX IDX_DED1C292A76ED395 ON post_likes (user_id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CF675F31B FOREIGN KEY (author_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C4B89032C FOREIGN KEY (post_id) REFERENCES post (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA77E3C61F9 FOREIGN KEY (owner_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event_user ADD CONSTRAINT FK_92589AE271F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event_user ADD CONSTRAINT FK_92589AE2A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE group_user ADD CONSTRAINT FK_A4C98D39FE54D947 FOREIGN KEY (group_id) REFERENCES "group" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE group_user ADD CONSTRAINT FK_A4C98D39A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE group_admins ADD CONSTRAINT FK_7166CDDFFE54D947 FOREIGN KEY (group_id) REFERENCES "group" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE group_admins ADD CONSTRAINT FK_7166CDDFA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF675F31B FOREIGN KEY (author_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FB9ADA51B FOREIGN KEY (in_group_id) REFERENCES "group" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE poll ADD CONSTRAINT FK_84BCFA454B89032C FOREIGN KEY (post_id) REFERENCES post (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE poll_choice ADD CONSTRAINT FK_2DAE19C93C947C0F FOREIGN KEY (poll_id) REFERENCES poll (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE poll_choice_user ADD CONSTRAINT FK_EA6E1E6852514F25 FOREIGN KEY (poll_choice_id) REFERENCES poll_choice (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE poll_choice_user ADD CONSTRAINT FK_EA6E1E68A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DF675F31B FOREIGN KEY (author_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D71F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE post_likes ADD CONSTRAINT FK_DED1C2924B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE post_likes ADD CONSTRAINT FK_DED1C292A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE comment DROP CONSTRAINT FK_9474526CF675F31B');
        $this->addSql('ALTER TABLE event DROP CONSTRAINT FK_3BAE0AA77E3C61F9');
        $this->addSql('ALTER TABLE event_user DROP CONSTRAINT FK_92589AE2A76ED395');
        $this->addSql('ALTER TABLE group_user DROP CONSTRAINT FK_A4C98D39A76ED395');
        $this->addSql('ALTER TABLE group_admins DROP CONSTRAINT FK_7166CDDFA76ED395');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307FF675F31B');
        $this->addSql('ALTER TABLE poll_choice_user DROP CONSTRAINT FK_EA6E1E68A76ED395');
        $this->addSql('ALTER TABLE post DROP CONSTRAINT FK_5A8A6C8DF675F31B');
        $this->addSql('ALTER TABLE post_likes DROP CONSTRAINT FK_DED1C292A76ED395');
        $this->addSql('ALTER TABLE event_user DROP CONSTRAINT FK_92589AE271F7E88B');
        $this->addSql('ALTER TABLE post DROP CONSTRAINT FK_5A8A6C8D71F7E88B');
        $this->addSql('ALTER TABLE group_user DROP CONSTRAINT FK_A4C98D39FE54D947');
        $this->addSql('ALTER TABLE group_admins DROP CONSTRAINT FK_7166CDDFFE54D947');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307FB9ADA51B');
        $this->addSql('ALTER TABLE poll_choice DROP CONSTRAINT FK_2DAE19C93C947C0F');
        $this->addSql('ALTER TABLE poll_choice_user DROP CONSTRAINT FK_EA6E1E6852514F25');
        $this->addSql('ALTER TABLE comment DROP CONSTRAINT FK_9474526C4B89032C');
        $this->addSql('ALTER TABLE poll DROP CONSTRAINT FK_84BCFA454B89032C');
        $this->addSql('ALTER TABLE post_likes DROP CONSTRAINT FK_DED1C2924B89032C');
        $this->addSql('DROP SEQUENCE app_user_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE comment_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE event_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "group_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE message_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE poll_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE poll_choice_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE post_id_seq CASCADE');
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE event_user');
        $this->addSql('DROP TABLE "group"');
        $this->addSql('DROP TABLE group_user');
        $this->addSql('DROP TABLE group_admins');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE poll');
        $this->addSql('DROP TABLE poll_choice');
        $this->addSql('DROP TABLE poll_choice_user');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE post_likes');
    }
}
