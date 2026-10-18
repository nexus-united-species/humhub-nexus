<?php

use humhub\components\Migration;

/** Offene Tuer: wer gerade ansprechbar ist und in welchem Raum. Hoechstens eine Zeile je Mensch. */
class m261018_120000_tuer extends Migration
{
    public function safeUp()
    {
        $this->safeCreateTable('nexus_meeting_tuer', [
            'user_id' => $this->integer()->notNull(),
            // jedes Oeffnen ein neuer Raumname -- ein alter Link fuehrt nicht spaeter ins Gespraech
            'raum' => $this->string(80)->notNull(),
            'warteraum' => $this->boolean()->notNull()->defaultValue(false),
            'geoeffnet_am' => $this->dateTime()->notNull(),
            // vom Browser der Person jede Minute erneuert, solange ihr Gespraechsfenster offen ist
            'lebenszeichen_am' => $this->dateTime()->notNull(),
            'PRIMARY KEY(user_id)',
        ]);
        $this->safeAddForeignKey('fk-nexus_meeting_tuer-user', 'nexus_meeting_tuer', 'user_id', 'user', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->safeDropTable('nexus_meeting_tuer');
    }
}
