<?php

use yii\db\Migration;

/**
 * Laengere Uebersetzungen speichern: TEXT fasst nur 64 kB. Eine 41-kB-Datei wird auf Russisch rund doppelt so gross,
 * das Speichern scheiterte und die Datei haette bei jedem Aufruf neu uebersetzt werden muessen.
 */
class m261101_120000_cache_mediumtext extends Migration
{
    public function safeUp()
    {
        $this->execute('ALTER TABLE nexus_translation_cache MODIFY translated_text MEDIUMTEXT NOT NULL');
    }

    public function safeDown()
    {
        return false;
    }
}
