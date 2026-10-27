<?php

use humhub\components\Migration;

/**
 * Eine Kachel kann erst ab einem bestimmten Monat gelten ("ab_monat", Form JJJJ-MM). Davor erscheint sie
 * nicht in der Ampel und bekommt kein Geld aus dem Topf.
 */
class m261027_120000_ab_monat extends Migration
{
    public function safeUp()
    {
        $this->addColumn('nexus_fin_kachel', 'ab_monat', $this->string(7)->null());
    }

    public function safeDown()
    {
        $this->dropColumn('nexus_fin_kachel', 'ab_monat');
    }
}
