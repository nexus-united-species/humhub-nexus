<?php

use humhub\components\Migration;

/**
 * Atomarer Dopplungsschutz fuer Direktnachrichten-Antworten -- derselbe
 * message_entry_id darf nie zweimal eine Antwort ausloesen. Eigene Tabelle
 * statt Wiederverwendung von nexus_mention_reply, weil der Primaerschluessel
 * dort auf eine andere Objektart (Mentioning-Zeilen) zeigt.
 */
class m260909_170000_message_reply extends Migration
{
    public function safeUp()
    {
        $this->createTable('nexus_message_reply', [
            'message_entry_id' => $this->integer()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
        ]);
        $this->addPrimaryKey('pk-nexus_message_reply', 'nexus_message_reply', 'message_entry_id');
    }

    public function safeDown()
    {
        $this->dropTable('nexus_message_reply');
    }
}
