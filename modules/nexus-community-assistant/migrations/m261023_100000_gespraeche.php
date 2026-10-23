<?php

use humhub\components\Migration;

/**
 * Nova-Gespraeche (23.10.2026): Mitglieder fuehren eigene Gespraeche mit Nova (Frage, Kritik
 * beantworten, Ideen, Text ueberarbeiten). Jedes Gespraech gehoert genau einem Menschen.
 *
 * - nexus_nova_gespraech / nexus_nova_nachricht: die Gespraeche und ihr Verlauf
 * - nexus_nova_merk: was Nova sich ueber den Menschen merkt -- er sieht und loescht es selbst
 * - nexus_nova_nutzung: Zaehler je Mensch und Tag (Tageslimit, Kosten). Getrennt von den
 *   Nachrichten, damit Loeschen eines Gespraechs das Tageslimit nicht zuruecksetzt.
 */
class m261023_100000_gespraeche extends Migration
{
    public function safeUp()
    {
        $this->safeCreateTable('nexus_nova_gespraech', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'titel' => $this->string(120)->notNull(),
            'art' => $this->string(20)->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->safeCreateIndex('idx-nexus_nova_gespraech-user', 'nexus_nova_gespraech', ['user_id', 'updated_at']);
        $this->safeAddForeignKey('fk-nexus_nova_gespraech-user', 'nexus_nova_gespraech', 'user_id', 'user', 'id', 'CASCADE');

        $this->safeCreateTable('nexus_nova_nachricht', [
            'id' => $this->primaryKey(),
            'gespraech_id' => $this->integer()->notNull(),
            'rolle' => $this->string(10)->notNull(),
            'inhalt' => $this->text()->notNull(),
            'quellen' => $this->text()->null(),
            'kosten' => $this->decimal(10, 5)->notNull()->defaultValue(0),
            'created_at' => $this->dateTime()->notNull(),
        ]);
        $this->safeCreateIndex('idx-nexus_nova_nachricht-gespraech', 'nexus_nova_nachricht', 'gespraech_id');
        $this->safeAddForeignKey('fk-nexus_nova_nachricht-gespraech', 'nexus_nova_nachricht', 'gespraech_id', 'nexus_nova_gespraech', 'id', 'CASCADE');

        $this->safeCreateTable('nexus_nova_merk', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'text' => $this->string(300)->notNull(),
            'created_at' => $this->dateTime()->notNull(),
        ]);
        $this->safeCreateIndex('idx-nexus_nova_merk-user', 'nexus_nova_merk', 'user_id');
        $this->safeAddForeignKey('fk-nexus_nova_merk-user', 'nexus_nova_merk', 'user_id', 'user', 'id', 'CASCADE');

        $this->safeCreateTable('nexus_nova_nutzung', [
            'user_id' => $this->integer()->notNull(),
            'datum' => $this->date()->notNull(),
            'anzahl' => $this->integer()->notNull()->defaultValue(0),
            'kosten' => $this->decimal(10, 5)->notNull()->defaultValue(0),
            'PRIMARY KEY (user_id, datum)',
        ]);
        $this->safeAddForeignKey('fk-nexus_nova_nutzung-user', 'nexus_nova_nutzung', 'user_id', 'user', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->safeDropTable('nexus_nova_nutzung');
        $this->safeDropTable('nexus_nova_merk');
        $this->safeDropTable('nexus_nova_nachricht');
        $this->safeDropTable('nexus_nova_gespraech');
    }
}
