<?php

use humhub\components\Migration;

/**
 * "Suchende": Mitglieder, die in ihrer Region Mitstreiter fuer eine Gemeinschaft suchen
 * (Josh, 30.09.2026). Freiwillig, eine Zeile je Mensch, jederzeit selbst loeschbar.
 * Sichtbar nur fuer Angemeldete -- nie fuer Gaeste.
 */
class m260930_180000_suchende extends Migration
{
    public function safeUp()
    {
        $this->createTable('nexus_karte_suchende', [
            'user_id' => $this->integer()->notNull(),
            'ort' => $this->string(150)->notNull(),
            'lat' => $this->decimal(6, 2)->notNull(),
            'lng' => $this->decimal(6, 2)->notNull(),
            'nachricht' => $this->string(300)->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
            'PRIMARY KEY (user_id)',
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('nexus_karte_suchende');
    }
}
