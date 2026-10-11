<?php

use humhub\components\Migration;

class m261011_200000_init extends Migration
{
    public function safeUp()
    {
        $this->safeCreateTable('nexus_projekt', [
            'id' => $this->primaryKey(),
            'space_id' => $this->integer()->notNull(),
            'titel' => $this->string(190)->notNull(),
            'ziel' => $this->text()->null(),
            'leitung_id' => $this->integer()->null(),
            'frist' => $this->date()->null(),
            'status' => $this->string(20)->notNull()->defaultValue('aktiv'), // aktiv | abgeschlossen
            'created_at' => $this->dateTime()->notNull(),
            'created_by' => $this->integer()->null(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->safeCreateIndex('idx-nexus_projekt-space', 'nexus_projekt', ['space_id', 'status']);
        $this->safeAddForeignKey('fk-nexus_projekt-space', 'nexus_projekt', 'space_id', 'space', 'id', 'CASCADE');

        // Kachel = ein Arbeitsschritt des Projekts
        $this->safeCreateTable('nexus_projekt_karte', [
            'id' => $this->primaryKey(),
            'projekt_id' => $this->integer()->notNull(),
            'titel' => $this->string(190)->notNull(),
            'beschreibung' => $this->text()->null(),
            'spalte' => $this->string(20)->notNull()->defaultValue('offen'), // offen | arbeit | abstimmung | fertig
            'verantwortlich_id' => $this->integer()->null(),
            'frist' => $this->date()->null(),
            'abstimmung_bis' => $this->date()->null(),
            // Jede neue Abstimmung ist eine neue Runde -- Stimmen gelten nur fuer ihre Runde
            'runde' => $this->integer()->notNull()->defaultValue(0),
            'links' => $this->text()->null(), // JSON: [{"titel": ..., "url": ...}]
            'sortierung' => $this->integer()->notNull()->defaultValue(0),
            'erinnert_frist' => $this->date()->null(),
            'erinnert_ueberfaellig' => $this->date()->null(),
            'erinnert_runde' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->dateTime()->notNull(),
            'created_by' => $this->integer()->null(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->safeCreateIndex('idx-nexus_projekt_karte-projekt', 'nexus_projekt_karte', ['projekt_id', 'spalte']);
        $this->safeCreateIndex('idx-nexus_projekt_karte-verantwortlich', 'nexus_projekt_karte', 'verantwortlich_id');
        $this->safeAddForeignKey('fk-nexus_projekt_karte-projekt', 'nexus_projekt_karte', 'projekt_id', 'nexus_projekt', 'id', 'CASCADE');

        $this->safeCreateTable('nexus_projekt_helfer', [
            'karte_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
        ]);
        $this->safeAddPrimaryKey('pk-nexus_projekt_helfer', 'nexus_projekt_helfer', ['karte_id', 'user_id']);
        $this->safeCreateIndex('idx-nexus_projekt_helfer-user', 'nexus_projekt_helfer', 'user_id');
        $this->safeAddForeignKey('fk-nexus_projekt_helfer-karte', 'nexus_projekt_helfer', 'karte_id', 'nexus_projekt_karte', 'id', 'CASCADE');

        $this->safeCreateTable('nexus_projekt_schritt', [
            'id' => $this->primaryKey(),
            'karte_id' => $this->integer()->notNull(),
            'text' => $this->string(255)->notNull(),
            'erledigt' => $this->boolean()->notNull()->defaultValue(false),
            'sortierung' => $this->integer()->notNull()->defaultValue(0),
        ]);
        $this->safeAddForeignKey('fk-nexus_projekt_schritt-karte', 'nexus_projekt_schritt', 'karte_id', 'nexus_projekt_karte', 'id', 'CASCADE');

        // "Passt so" / "Ich habe eine Aenderung" -- eine Stimme je Mensch und Runde
        $this->safeCreateTable('nexus_projekt_stimme', [
            'id' => $this->primaryKey(),
            'karte_id' => $this->integer()->notNull(),
            'runde' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'art' => $this->string(20)->notNull(), // ok | aenderung
            'text' => $this->text()->null(),
            'created_at' => $this->dateTime()->notNull(),
        ]);
        $this->safeCreateIndex('ux-nexus_projekt_stimme', 'nexus_projekt_stimme', ['karte_id', 'runde', 'user_id'], true);
        $this->safeAddForeignKey('fk-nexus_projekt_stimme-karte', 'nexus_projekt_stimme', 'karte_id', 'nexus_projekt_karte', 'id', 'CASCADE');

        // "Ich mache mit": wer erinnert wird, wenn eine Abstimmung laeuft
        $this->safeCreateTable('nexus_projekt_mitmacher', [
            'projekt_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
        ]);
        $this->safeAddPrimaryKey('pk-nexus_projekt_mitmacher', 'nexus_projekt_mitmacher', ['projekt_id', 'user_id']);
        $this->safeAddForeignKey('fk-nexus_projekt_mitmacher-projekt', 'nexus_projekt_mitmacher', 'projekt_id', 'nexus_projekt', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        foreach (['nexus_projekt_mitmacher', 'nexus_projekt_stimme', 'nexus_projekt_schritt', 'nexus_projekt_helfer', 'nexus_projekt_karte', 'nexus_projekt'] as $t) {
            $this->safeDropTable($t);
        }
    }
}
