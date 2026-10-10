<?php

use humhub\components\Migration;

class m261010_120000_init extends Migration
{
    public function safeUp()
    {
        $this->safeCreateTable('nexus_gesundheit_artikel', [
            'id' => $this->primaryKey(),
            // Vollstaendiger Original-Dateiname aus dem Manifest -- eindeutig, anders als die G-Nummer
            'schluessel' => $this->string(255)->notNull(),
            'gnummer' => $this->string(20)->null(),
            // Dateiname im Artikel-Ordner -- Artikel verlinken einander ueber relative .md-Pfade
            'datei' => $this->string(190)->notNull(),
            'slug' => $this->string(190)->notNull(),
            'titel' => $this->string(255)->notNull(),
            'kurztitel' => $this->string(190)->notNull(),
            'thema' => $this->string(40)->notNull(),
            'inhalt' => 'MEDIUMTEXT NOT NULL',
            'pruefdatum' => $this->date()->null(),
            'wiedervorlage' => $this->date()->null(),
            'erinnert_am' => $this->date()->null(),
            'import_hash' => $this->string(64)->null(),
            'im_portal_geaendert' => $this->boolean()->notNull()->defaultValue(false),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
            'updated_by' => $this->integer()->null(),
        ]);
        $this->safeCreateIndex('ux-nexus_gesundheit_artikel-schluessel', 'nexus_gesundheit_artikel', 'schluessel', true);
        $this->safeCreateIndex('ux-nexus_gesundheit_artikel-slug', 'nexus_gesundheit_artikel', 'slug', true);
        $this->safeCreateIndex('idx-nexus_gesundheit_artikel-thema', 'nexus_gesundheit_artikel', 'thema');

        // Schlagworte (sichtbar) und Suchwoerter (Alltagswoerter, Synonyme -- nur fuer die Suche)
        $this->safeCreateTable('nexus_gesundheit_wort', [
            'id' => $this->primaryKey(),
            'artikel_id' => $this->integer()->notNull(),
            'wort' => $this->string(120)->notNull(),
            'art' => $this->string(20)->notNull(), // 'schlagwort' | 'suchwort'
        ]);
        $this->safeCreateIndex('idx-nexus_gesundheit_wort-wort', 'nexus_gesundheit_wort', 'wort');
        $this->safeCreateIndex('ux-nexus_gesundheit_wort', 'nexus_gesundheit_wort', ['artikel_id', 'wort', 'art'], true);
        $this->safeAddForeignKey('fk-nexus_gesundheit_wort-artikel', 'nexus_gesundheit_wort', 'artikel_id', 'nexus_gesundheit_artikel', 'id', 'CASCADE');

        $this->safeCreateTable('nexus_gesundheit_version', [
            'id' => $this->primaryKey(),
            'artikel_id' => $this->integer()->notNull(),
            'titel' => $this->string(255)->notNull(),
            'inhalt' => 'MEDIUMTEXT NOT NULL',
            'grund' => $this->string(255)->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'created_by' => $this->integer()->null(),
        ]);
        $this->safeCreateIndex('idx-nexus_gesundheit_version-artikel', 'nexus_gesundheit_version', 'artikel_id');
        $this->safeAddForeignKey('fk-nexus_gesundheit_version-artikel', 'nexus_gesundheit_version', 'artikel_id', 'nexus_gesundheit_artikel', 'id', 'CASCADE');

        $this->safeCreateTable('nexus_gesundheit_vorschlag', [
            'id' => $this->primaryKey(),
            'artikel_id' => $this->integer()->notNull(),
            'art' => $this->string(20)->notNull(), // 'ergaenzen' | 'aendern'
            'text' => $this->text()->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('offen'), // offen | uebernommen | abgelehnt
            'antwort' => $this->text()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'erledigt_am' => $this->dateTime()->null(),
            'erledigt_von' => $this->integer()->null(),
        ]);
        $this->safeCreateIndex('idx-nexus_gesundheit_vorschlag-status', 'nexus_gesundheit_vorschlag', 'status');
        $this->safeAddForeignKey('fk-nexus_gesundheit_vorschlag-artikel', 'nexus_gesundheit_vorschlag', 'artikel_id', 'nexus_gesundheit_artikel', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->safeDropTable('nexus_gesundheit_vorschlag');
        $this->safeDropTable('nexus_gesundheit_version');
        $this->safeDropTable('nexus_gesundheit_wort');
        $this->safeDropTable('nexus_gesundheit_artikel');
    }
}
