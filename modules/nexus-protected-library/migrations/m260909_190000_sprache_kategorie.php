<?php

use humhub\components\Migration;

/**
 * Fuegt Sprache und Medientyp hinzu, um die Bibliothek zweistufig zu
 * gliedern (Sprache -> Kategorie -> Buecher), wie von Josh am 09.09.2026
 * gewuenscht. Bestehende Buecher (die drei deutschen Romane) werden auf
 * de/roman gesetzt.
 */
class m260909_190000_sprache_kategorie extends Migration
{
    public function safeUp()
    {
        $this->addColumn('nexus_book', 'language', $this->string(5)->notNull()->defaultValue('de'));
        $this->addColumn('nexus_book', 'media_type', $this->string(20)->notNull()->defaultValue('roman'));

        $this->update('nexus_book', ['language' => 'de', 'media_type' => 'roman']);
    }

    public function safeDown()
    {
        $this->dropColumn('nexus_book', 'media_type');
        $this->dropColumn('nexus_book', 'language');
    }
}
