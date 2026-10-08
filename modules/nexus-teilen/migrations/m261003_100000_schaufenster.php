<?php

use humhub\components\Migration;

/**
 * Schaufenster (Josh, 03.10.2026): Admins stellen freigegebene Inhalte mit einem Klick auf die
 * oeffentliche Seite "Aus der N.E.X.U.S.-Gemeinschaft". Eine Freigabe (= oeffentlicher Link) kann
 * es ohne Schaufenster geben, umgekehrt nicht.
 */
class m261003_100000_schaufenster extends Migration
{
    public function safeUp()
    {
        $this->addColumn('nexus_teilen_freigabe', 'schaufenster', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn('nexus_teilen_freigabe', 'schaufenster_at', $this->dateTime()->null());
        $this->addColumn('nexus_teilen_freigabe', 'schaufenster_von', $this->integer()->null());
        $this->createIndex('ix_nexus_teilen_schaufenster', 'nexus_teilen_freigabe', ['schaufenster', 'schaufenster_at']);
    }

    public function safeDown()
    {
        $this->dropIndex('ix_nexus_teilen_schaufenster', 'nexus_teilen_freigabe');
        $this->dropColumn('nexus_teilen_freigabe', 'schaufenster_von');
        $this->dropColumn('nexus_teilen_freigabe', 'schaufenster_at');
        $this->dropColumn('nexus_teilen_freigabe', 'schaufenster');
    }
}
