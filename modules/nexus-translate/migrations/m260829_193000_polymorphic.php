<?php

use yii\db\Migration;

/**
 * Die urspruengliche Tabelle war fest an "post" gebunden (Fremdschluessel).
 * Jetzt, wo auch Kommentare uebersetzt werden, braucht es eine Spalte, die
 * sagt WELCHE Art von Inhalt gemeint ist -- ein Fremdschluessel ginge
 * dafuer nicht mehr, da content_id je nach content_type in einer anderen
 * Tabelle (post ODER comment) steht.
 *
 * Bewusst neu erstellt statt per ALTER TABLE umgebaut: Die Tabelle enthielt
 * zu diesem Zeitpunkt ausschliesslich eigene Testdaten, kein Risiko.
 */
class m260829_193000_polymorphic extends Migration
{
    public function safeUp()
    {
        $this->dropTable('nexus_translation_cache');
        $this->createTable('nexus_translation_cache', [
            'content_type' => $this->string(10)->notNull(),
            'content_id' => $this->integer()->notNull(),
            'language' => $this->string(10)->notNull(),
            'translated_text' => $this->text()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
        ]);
        $this->addPrimaryKey(
            'pk_nexus_translation_cache',
            'nexus_translation_cache',
            ['content_type', 'content_id', 'language']
        );
    }

    public function safeDown()
    {
        $this->dropTable('nexus_translation_cache');
    }
}
