<?php

use humhub\components\Migration;

/**
 * Eine Zeile je Gemeinschaft. Die Zeile selbst bedeutet "dieser Kreis ist eine Gemeinschaft"
 * (legt nur ein Systemadmin an); auf der Karte erscheint er erst, wenn lat/lng gesetzt sind.
 */
class m260930_120000_init extends Migration
{
    public function safeUp()
    {
        $this->createTable('nexus_gemeinschaft_ort', [
            'space_id' => $this->integer()->notNull(),
            'ort' => $this->string(150)->null(),
            'lat' => $this->decimal(6, 2)->null(),
            'lng' => $this->decimal(6, 2)->null(),
            'updated_by' => $this->integer()->null(),
            'updated_at' => $this->dateTime()->notNull(),
            'PRIMARY KEY (space_id)',
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('nexus_gemeinschaft_ort');
    }
}
