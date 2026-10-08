<?php

use humhub\components\Migration;

/**
 * Wer hat Novas Begruessung schon bekommen? Eine Zeile je Mensch -- niemand wird zweimal begruesst.
 * art: "neu" (3 Stunden nach dem ersten Betreten) oder "nachgeholt" (einmalige Runde fuer die,
 * die sich vor dem Start angemeldet, aber noch nichts geschrieben hatten).
 */
class m261002_160000_willkommen extends Migration
{
    public function safeUp()
    {
        $this->createTable('nexus_willkommen', [
            'user_id' => $this->integer()->notNull(),
            'art' => $this->string(12)->notNull(),
            'kreis_id' => $this->integer()->null(),
            'sent_at' => $this->dateTime()->notNull(),
            'PRIMARY KEY (user_id)',
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('nexus_willkommen');
    }
}
