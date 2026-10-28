<?php

use humhub\components\Migration;

/**
 * Emoji-Reaktionen (28.10.2026): eine Zeile je Mensch und Beitrag/Kommentar. object_model/object_id wie in
 * HumHubs `like`-Tabelle (Beitrag oder Kommentar), damit dieselben Objekte gemeint sind.
 */
class m261028_100000_init extends Migration
{
    public function safeUp()
    {
        $this->safeCreateTable('nexus_reaktion', [
            'id' => $this->primaryKey(),
            'object_model' => $this->string(190)->notNull(),
            'object_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'emoji' => $this->string(16)->notNull(),
            'created_at' => $this->dateTime()->notNull(),
        ]);
        $this->safeCreateIndex('uniq-nexus_reaktion-objekt-mensch', 'nexus_reaktion', ['object_model', 'object_id', 'user_id'], true);
        $this->safeCreateIndex('idx-nexus_reaktion-user', 'nexus_reaktion', 'user_id');
    }

    public function safeDown()
    {
        $this->safeDropTable('nexus_reaktion');
    }
}
