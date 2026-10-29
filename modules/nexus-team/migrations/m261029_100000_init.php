<?php

use humhub\components\Migration;

/**
 * Wer hat ein neues Mitglied persoenlich begruesst? Eine Zeile je Mensch (die erste Begruessung zaehlt);
 * das Team sieht so, wen noch niemand begruesst hat. Speichert nichts ueber das Verhalten der Mitglieder.
 */
class m261029_100000_init extends Migration
{
    public function safeUp()
    {
        $this->createTable('nexus_team_begruesst', [
            'user_id' => $this->integer()->notNull(),
            'von_id' => $this->integer()->notNull(),
            'am' => $this->dateTime()->notNull(),
            'PRIMARY KEY (user_id)',
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('nexus_team_begruesst');
    }
}
