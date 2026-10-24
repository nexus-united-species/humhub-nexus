<?php

use humhub\components\Migration;

/**
 * Teams (25.10.2026): Arbeit, die Menschen dauerhaft gemeinsam tragen (z. B. Community-Begleitung).
 * Eine Kreisleitung schreibt ein Team mit Auftrag und Aufgaben aus, Interessierte melden sich, nach einem
 * Gespraech nimmt die Leitung sie auf. Das Team regelt Zustaendigkeit, Vertretung und Arbeitsweise selbst und
 * hat ein gemeinsames Postfach (Unterhaltungen des Nachrichten-Moduls).
 *
 * - nexus_team: das Team (Kreis, Auftrag, Status, Koordination, Arbeitsweise, eigene Projekt-Tafel)
 * - nexus_team_aufgabe: regelmaessige oder einmalige Aufgaben mit zustaendiger Person und Vertretung
 * - nexus_team_mitglied: Anfrage -> dabei -> beendet, mit Zeitangabe und Abwesenheit
 * - nexus_team_post: Team-Postfach -- welche Unterhaltung, wer hat sie uebernommen, Status
 */
class m261025_100000_teams extends Migration
{
    public function safeUp()
    {
        $this->safeCreateTable('nexus_team', [
            'id' => $this->primaryKey(),
            'space_id' => $this->integer()->notNull(),
            'titel' => $this->string(120)->notNull(),
            'auftrag' => $this->text()->null(),
            'status' => $this->string(12)->notNull()->defaultValue('suchend'),
            'koordination_id' => $this->integer()->null(),
            'arbeitsweise' => $this->text()->null(),
            'projekt_id' => $this->integer()->null(),
            'created_by' => $this->integer()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->safeCreateIndex('idx-nexus_team-space', 'nexus_team', 'space_id');
        $this->safeAddForeignKey('fk-nexus_team-space', 'nexus_team', 'space_id', 'space', 'id', 'CASCADE');

        $this->safeCreateTable('nexus_team_aufgabe', [
            'id' => $this->primaryKey(),
            'team_id' => $this->integer()->notNull(),
            'titel' => $this->string(190)->notNull(),
            'art' => $this->string(12)->notNull()->defaultValue('regelmaessig'),
            'aufwand' => $this->string(100)->null(),
            'zustaendig_id' => $this->integer()->null(),
            'vertretung_id' => $this->integer()->null(),
            'erledigt' => $this->boolean()->notNull()->defaultValue(false),
            'sortierung' => $this->integer()->notNull()->defaultValue(0),
        ]);
        $this->safeCreateIndex('idx-nexus_team_aufgabe-team', 'nexus_team_aufgabe', 'team_id');
        $this->safeAddForeignKey('fk-nexus_team_aufgabe-team', 'nexus_team_aufgabe', 'team_id', 'nexus_team', 'id', 'CASCADE');

        $this->safeCreateTable('nexus_team_mitglied', [
            'id' => $this->primaryKey(),
            'team_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'status' => $this->string(12)->notNull()->defaultValue('angefragt'),
            'zeit' => $this->string(190)->null(),
            'nachricht' => $this->text()->null(),
            'aufgaben' => $this->string(255)->null(),
            'weg_bis' => $this->date()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'entschieden_von' => $this->integer()->null(),
            'entschieden_am' => $this->dateTime()->null(),
        ]);
        $this->safeCreateIndex('ux-nexus_team_mitglied', 'nexus_team_mitglied', ['team_id', 'user_id'], true);
        $this->safeAddForeignKey('fk-nexus_team_mitglied-team', 'nexus_team_mitglied', 'team_id', 'nexus_team', 'id', 'CASCADE');
        $this->safeAddForeignKey('fk-nexus_team_mitglied-user', 'nexus_team_mitglied', 'user_id', 'user', 'id', 'CASCADE');

        $this->safeCreateTable('nexus_team_post', [
            'id' => $this->primaryKey(),
            'team_id' => $this->integer()->notNull(),
            'message_id' => $this->integer()->notNull(),
            'betreff' => $this->string(255)->notNull(),
            'quelle' => $this->string(12)->notNull(),
            'status' => $this->string(14)->notNull()->defaultValue('offen'),
            'uebernommen_von' => $this->integer()->null(),
            'created_by' => $this->integer()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->safeCreateIndex('ux-nexus_team_post', 'nexus_team_post', ['team_id', 'message_id'], true);
        $this->safeAddForeignKey('fk-nexus_team_post-team', 'nexus_team_post', 'team_id', 'nexus_team', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->safeDropTable('nexus_team_post');
        $this->safeDropTable('nexus_team_mitglied');
        $this->safeDropTable('nexus_team_aufgabe');
        $this->safeDropTable('nexus_team');
    }
}
