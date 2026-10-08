<?php

use yii\db\Migration;

/**
 * Uebersetzte Kreis-Namen und -Kurzbeschreibungen (Josh, 25.09.2026).
 *
 * Gespeichert wird je Kreis, Zielsprache und Feld EINE Uebersetzung samt
 * Pruefsumme des deutschen Originals: aendert jemand Name oder
 * Beschreibung, passt die Pruefsumme nicht mehr, und es wird neu uebersetzt.
 * "geprueft" markiert, was ein Mensch durchgesehen hat.
 */
class m260925_120000_space_translation extends Migration
{
    public function safeUp()
    {
        $this->createTable('nexus_space_translation', [
            'space_id' => $this->integer()->notNull(),
            'language' => $this->string(10)->notNull(),
            'feld' => $this->string(20)->notNull(),
            'quelle_hash' => $this->char(32)->notNull(),
            'text' => $this->text()->notNull(),
            'geprueft' => $this->boolean()->notNull()->defaultValue(false),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->addPrimaryKey('pk_nexus_space_translation', 'nexus_space_translation', ['space_id', 'language', 'feld']);
    }

    public function safeDown()
    {
        $this->dropTable('nexus_space_translation');
    }
}
