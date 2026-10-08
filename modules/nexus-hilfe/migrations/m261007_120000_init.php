<?php

use humhub\components\Migration;

/**
 * Eine Zeile je Support-Anfrage. Die ID ist die Anfrage-Nummer (#12), die der Mensch in der
 * Bestaetigung bekommt. Name/E-Mail nur bei Anfragen ohne Anmeldung (sonst steht der Mensch in user_id).
 */
class m261007_120000_init extends Migration
{
    public function safeUp()
    {
        $this->createTable('nexus_hilfe_anfrage', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->null(),
            'name' => $this->string(120)->null(),
            'email' => $this->string(190)->null(),
            'thema' => $this->string(20)->notNull(),
            'text' => $this->text()->notNull(),
            'quelle' => $this->string(12)->notNull(),
            'post_id' => $this->integer()->null(),
            'created_at' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex('ix_nexus_hilfe_user', 'nexus_hilfe_anfrage', 'user_id');
    }

    public function safeDown()
    {
        $this->dropTable('nexus_hilfe_anfrage');
    }
}
