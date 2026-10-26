<?php

use humhub\components\Migration;

/**
 * Einzelne Artikel oeffentlich lesbar machen (ohne Anmeldung). Der Schluessel ist 128 Bit Zufall und
 * steht in der Adresse; wer ihn nicht kennt, kommt nicht hin. Zuruecknehmen = Schluessel loeschen.
 */
class m261026_160000_oeffentlich extends Migration
{
    public function safeUp()
    {
        $this->addColumn('nexus_wissen_artikel', 'oeffentlich_schluessel', $this->string(32)->null());
        $this->addColumn('nexus_wissen_artikel', 'oeffentlich_am', $this->dateTime()->null());
        $this->addColumn('nexus_wissen_artikel', 'oeffentlich_von', $this->integer()->null());
        $this->createIndex('idx-nexus_wissen_artikel-oeffentlich', 'nexus_wissen_artikel', 'oeffentlich_schluessel', true);
    }

    public function safeDown()
    {
        $this->dropIndex('idx-nexus_wissen_artikel-oeffentlich', 'nexus_wissen_artikel');
        $this->dropColumn('nexus_wissen_artikel', 'oeffentlich_von');
        $this->dropColumn('nexus_wissen_artikel', 'oeffentlich_am');
        $this->dropColumn('nexus_wissen_artikel', 'oeffentlich_schluessel');
    }
}
