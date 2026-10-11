<?php

use humhub\components\Migration;

/**
 * Ein Projekt kann mehrere Leitungen haben (z. B. zwei Menschen, die es gemeinsam fuehren).
 * Die bisherige Spalte leitung_id wird in die neue Tabelle uebernommen und entfernt.
 */
class m261011_210000_mehrere_leitungen extends Migration
{
    public function safeUp()
    {
        $this->safeCreateTable('nexus_projekt_leitung', [
            'projekt_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
        ]);
        $this->safeAddPrimaryKey('pk-nexus_projekt_leitung', 'nexus_projekt_leitung', ['projekt_id', 'user_id']);
        $this->safeCreateIndex('idx-nexus_projekt_leitung-user', 'nexus_projekt_leitung', 'user_id');
        $this->safeAddForeignKey('fk-nexus_projekt_leitung-projekt', 'nexus_projekt_leitung', 'projekt_id', 'nexus_projekt', 'id', 'CASCADE');
        $this->execute('INSERT INTO nexus_projekt_leitung (projekt_id, user_id) SELECT id, leitung_id FROM nexus_projekt WHERE leitung_id IS NOT NULL');
        $this->dropColumn('nexus_projekt', 'leitung_id');
    }

    public function safeDown()
    {
        $this->addColumn('nexus_projekt', 'leitung_id', $this->integer()->null());
        $this->execute('UPDATE nexus_projekt p SET leitung_id = (SELECT MIN(user_id) FROM nexus_projekt_leitung l WHERE l.projekt_id = p.id)');
        $this->safeDropTable('nexus_projekt_leitung');
    }
}
