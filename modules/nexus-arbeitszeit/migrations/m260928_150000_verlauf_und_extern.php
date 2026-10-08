<?php

use humhub\components\Migration;

/**
 * - verlauf: Aenderungsprotokoll je Eintrag ("28.09.2026 Josh Richman: Stunden 2 -> 1,5"), weil
 *   Admins Eintraege nachtraeglich bearbeiten koennen (Josh, 28.09.2026) -- nachvollziehbar bleibt,
 *   wer was geaendert hat.
 * - name_extern + user_id optional: Uebernahme der Telegram-Stunden aus dem Google-Sheet. Wer dort
 *   Stunden hat, aber (noch) kein Portal-Konto, geht so nicht verloren.
 */
class m260928_150000_verlauf_und_extern extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('nexus_arbeitszeit', 'user_id', $this->integer()->null());
        $this->addColumn('nexus_arbeitszeit', 'name_extern', $this->string(255)->null()->after('user_id'));
        $this->addColumn('nexus_arbeitszeit', 'verlauf', $this->text()->null());
    }

    public function safeDown()
    {
        $this->dropColumn('nexus_arbeitszeit', 'verlauf');
        $this->dropColumn('nexus_arbeitszeit', 'name_extern');
    }
}
