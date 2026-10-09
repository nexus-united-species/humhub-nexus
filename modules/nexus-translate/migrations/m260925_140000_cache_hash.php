<?php

use humhub\components\Migration;

/**
 * Pruefsumme des Originals an jeder Uebersetzung (25.09.2026): die automatische
 * Uebersetzung soll nach dem Bearbeiten eines Beitrags neu entstehen, statt die
 * alte Fassung weiter anzuzeigen.
 */
class m260925_140000_cache_hash extends Migration
{
    public function safeUp()
    {
        $this->addColumn('nexus_translation_cache', 'quelle_hash', $this->char(32)->null());
    }

    public function safeDown()
    {
        $this->dropColumn('nexus_translation_cache', 'quelle_hash');
    }
}
