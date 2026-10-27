<?php

use humhub\components\Migration;

/**
 * Antworten auf Rueckmeldungen (27.10.2026): Zu einer Rueckmeldung ("Ich habe eine Aenderung", Bedenken, Einwand)
 * kann jedes Kreismitglied antworten -- ein Gespraech unter der Rueckmeldung, damit Fragen und Klaerungen nicht
 * in privaten Nachrichten oder im Kreis-Stream verschwinden. Antworten haengen an der Stimme und bleiben mit ihr
 * sichtbar (auch in frueheren Runden).
 */
class m261027_100000_antworten extends Migration
{
    public function safeUp()
    {
        $this->safeCreateTable('nexus_projekt_antwort', [
            'id' => $this->primaryKey(),
            'stimme_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'text' => $this->text()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
        ]);
        $this->safeCreateIndex('idx-nexus_projekt_antwort-stimme', 'nexus_projekt_antwort', 'stimme_id');
        $this->safeAddForeignKey('fk-nexus_projekt_antwort-stimme', 'nexus_projekt_antwort', 'stimme_id', 'nexus_projekt_stimme', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->safeDropTable('nexus_projekt_antwort');
    }
}
