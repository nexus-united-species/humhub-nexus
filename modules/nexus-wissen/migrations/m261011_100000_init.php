<?php

use humhub\components\Migration;

class m261011_100000_init extends Migration
{
    public function safeUp()
    {
        $this->safeCreateTable('nexus_wissen_artikel', [
            'id' => $this->primaryKey(),
            // Kennung der Sammlung aus den Einstellungen (z. B. "gesundheit")
            'sammlung' => $this->string(64)->notNull(),
            // Eindeutig je Sammlung: Manifest-Feld "schluessel"/"original" oder der Dateipfad
            'schluessel' => $this->string(255)->notNull(),
            // Kurze Kennung fuer die Redaktion (z. B. "G0066A"), nur fuer Bearbeitende sichtbar
            'kennung' => $this->string(40)->null(),
            // Dateiname -- Artikel verlinken einander ueber relative .md-Pfade
            'datei' => $this->string(190)->notNull(),
            'slug' => $this->string(190)->notNull(),
            'titel' => $this->string(255)->notNull(),
            'kurztitel' => $this->string(190)->notNull(),
            'thema' => $this->string(100)->notNull()->defaultValue(''),
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
        $this->safeCreateIndex('ux-nexus_wissen_artikel-schluessel', 'nexus_wissen_artikel', ['sammlung', 'schluessel'], true);
        $this->safeCreateIndex('ux-nexus_wissen_artikel-slug', 'nexus_wissen_artikel', ['sammlung', 'slug'], true);
        $this->safeCreateIndex('idx-nexus_wissen_artikel-thema', 'nexus_wissen_artikel', ['sammlung', 'thema']);

        // Schlagworte (sichtbar) und Suchwoerter (Alltagswoerter, Synonyme -- nur fuer die Suche)
        $this->safeCreateTable('nexus_wissen_wort', [
            'id' => $this->primaryKey(),
            'artikel_id' => $this->integer()->notNull(),
            'wort' => $this->string(120)->notNull(),
            'art' => $this->string(20)->notNull(), // 'schlagwort' | 'suchwort'
        ]);
        $this->safeCreateIndex('idx-nexus_wissen_wort-wort', 'nexus_wissen_wort', 'wort');
        $this->safeCreateIndex('ux-nexus_wissen_wort', 'nexus_wissen_wort', ['artikel_id', 'wort', 'art'], true);
        $this->safeAddForeignKey('fk-nexus_wissen_wort-artikel', 'nexus_wissen_wort', 'artikel_id', 'nexus_wissen_artikel', 'id', 'CASCADE');

        $this->safeCreateTable('nexus_wissen_version', [
            'id' => $this->primaryKey(),
            'artikel_id' => $this->integer()->notNull(),
            'titel' => $this->string(255)->notNull(),
            'inhalt' => 'MEDIUMTEXT NOT NULL',
            'grund' => $this->string(255)->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'created_by' => $this->integer()->null(),
        ]);
        $this->safeCreateIndex('idx-nexus_wissen_version-artikel', 'nexus_wissen_version', 'artikel_id');
        $this->safeAddForeignKey('fk-nexus_wissen_version-artikel', 'nexus_wissen_version', 'artikel_id', 'nexus_wissen_artikel', 'id', 'CASCADE');

        $this->safeCreateTable('nexus_wissen_vorschlag', [
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
        $this->safeCreateIndex('idx-nexus_wissen_vorschlag-status', 'nexus_wissen_vorschlag', 'status');
        $this->safeAddForeignKey('fk-nexus_wissen_vorschlag-artikel', 'nexus_wissen_vorschlag', 'artikel_id', 'nexus_wissen_artikel', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->safeDropTable('nexus_wissen_vorschlag');
        $this->safeDropTable('nexus_wissen_version');
        $this->safeDropTable('nexus_wissen_wort');
        $this->safeDropTable('nexus_wissen_artikel');
    }
}
