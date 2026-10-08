<?php

use humhub\components\Migration;

/** Merkt sich, fuer wen die Admins schon benachrichtigt wurden (eine Zeile je Mensch) samt Antworten. */
class m260930_200000_init extends Migration
{
    public function safeUp()
    {
        $this->createTable('nexus_mitgliedsanfrage', [
            'user_id' => $this->integer()->notNull(),
            'antworten' => $this->text()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'PRIMARY KEY (user_id)',
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('nexus_mitgliedsanfrage');
    }
}
