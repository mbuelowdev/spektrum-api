<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260420190724 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE card (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, value_left VARCHAR(255) NOT NULL, value_right VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE TABLE guess (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, player_id INTEGER NOT NULL, room_id INTEGER NOT NULL, degree DOUBLE PRECISION NOT NULL, is_preview BOOLEAN NOT NULL, CONSTRAINT FK_32D30F9699E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_32D30F9654177093 FOREIGN KEY (room_id) REFERENCES room (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_32D30F9699E6F5DF ON guess (player_id)');
        $this->addSql('CREATE INDEX IDX_32D30F9654177093 ON guess (room_id)');
        $this->addSql('CREATE TABLE player (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(36) NOT NULL --(DC2Type:guid)
        , name VARCHAR(255) NOT NULL, profile_image BLOB DEFAULT NULL, wins INTEGER NOT NULL, last_heartbeat DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE room (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, game_active_player_id INTEGER DEFAULT NULL, game_active_card_id INTEGER DEFAULT NULL, uuid CHAR(36) NOT NULL --(DC2Type:guid)
        , name VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , game_state VARCHAR(255) DEFAULT NULL, game_points_team_a INTEGER DEFAULT NULL, game_points_team_b INTEGER DEFAULT NULL, game_round_index INTEGER DEFAULT NULL, game_target_degree DOUBLE PRECISION DEFAULT NULL, game_cluegiver_guess_text VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_729F519BB16171F7 FOREIGN KEY (game_active_player_id) REFERENCES player (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_729F519B134A7A72 FOREIGN KEY (game_active_card_id) REFERENCES card (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_729F519BB16171F7 ON room (game_active_player_id)');
        $this->addSql('CREATE INDEX IDX_729F519B134A7A72 ON room (game_active_card_id)');
        $this->addSql('CREATE TABLE room_players_team_a (room_id INTEGER NOT NULL, player_id INTEGER NOT NULL, PRIMARY KEY(room_id, player_id), CONSTRAINT FK_CBBE562B54177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_CBBE562B99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_CBBE562B54177093 ON room_players_team_a (room_id)');
        $this->addSql('CREATE INDEX IDX_CBBE562B99E6F5DF ON room_players_team_a (player_id)');
        $this->addSql('CREATE TABLE room_players_team_b (room_id INTEGER NOT NULL, player_id INTEGER NOT NULL, PRIMARY KEY(room_id, player_id), CONSTRAINT FK_52B7079154177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_52B7079199E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_52B7079154177093 ON room_players_team_b (room_id)');
        $this->addSql('CREATE INDEX IDX_52B7079199E6F5DF ON room_players_team_b (player_id)');
        $this->addSql('CREATE TABLE room_card (room_id INTEGER NOT NULL, card_id INTEGER NOT NULL, PRIMARY KEY(room_id, card_id), CONSTRAINT FK_751072B754177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_751072B74ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_751072B754177093 ON room_card (room_id)');
        $this->addSql('CREATE INDEX IDX_751072B74ACC9A20 ON room_card (card_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE card');
        $this->addSql('DROP TABLE guess');
        $this->addSql('DROP TABLE player');
        $this->addSql('DROP TABLE room');
        $this->addSql('DROP TABLE room_players_team_a');
        $this->addSql('DROP TABLE room_players_team_b');
        $this->addSql('DROP TABLE room_card');
    }
}
