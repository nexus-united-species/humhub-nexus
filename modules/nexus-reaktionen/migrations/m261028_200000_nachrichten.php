<?php

use humhub\components\Migration;

/**
 * "Antwort auf diese Nachricht" in privaten Unterhaltungen (28.10.2026): welche Nachricht antwortet auf welche.
 * Reaktionen in Nachrichten brauchen keine eigene Tabelle (nexus_reaktion, object_model = MessageEntry).
 */
class m261028_200000_nachrichten extends Migration
{
    public function safeUp()
    {
        $this->safeCreateTable('nexus_nachricht_antwort', [
            'entry_id' => $this->integer()->notNull(),
            'antwort_auf_id' => $this->integer()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'PRIMARY KEY(entry_id)',
        ]);
        $this->safeCreateIndex('idx-nexus_nachricht_antwort-auf', 'nexus_nachricht_antwort', 'antwort_auf_id');
    }

    public function safeDown()
    {
        $this->safeDropTable('nexus_nachricht_antwort');
    }
}
