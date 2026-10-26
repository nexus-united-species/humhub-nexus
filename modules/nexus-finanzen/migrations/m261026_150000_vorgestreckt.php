<?php

use humhub\components\Migration;

/**
 * Ausgaben, die jemand aus eigener Tasche vorgestreckt hat (noch nicht aus dem Topf erstattet), und
 * Kennzeichnung geschaetzter Betraege. Eine Ausgabe mit vorgestreckt_von zaehlt NICHT als Ausgabe aus dem
 * Spendentopf -- sie macht sichtbar, was bezahlt wurde, ohne den Topf zu verkleinern.
 */
class m261026_150000_vorgestreckt extends Migration
{
    public function safeUp()
    {
        $this->addColumn('nexus_fin_ausgabe', 'vorgestreckt_von', $this->integer()->null());
        $this->addColumn('nexus_fin_ausgabe', 'geschaetzt', $this->boolean()->notNull()->defaultValue(0));
    }

    public function safeDown()
    {
        $this->dropColumn('nexus_fin_ausgabe', 'geschaetzt');
        $this->dropColumn('nexus_fin_ausgabe', 'vorgestreckt_von');
    }
}
