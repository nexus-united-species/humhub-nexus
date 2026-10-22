<?php

use humhub\components\Migration;

/**
 * Bild-Auswahl (22.10.2026): eine Kachel kann mehrere Entwuerfe (Optionen) sammeln, ueber die im Konsent
 * abgestimmt wird -- je Entwurf Zustimmung, Bedenken oder schwerwiegender Einwand. Gewaehlt wird der
 * Entwurf ohne Einwand mit den meisten Zustimmungen.
 *
 * Die Stimmen liegen in der vorhandenen Stimmen-Tabelle (Spalte option_id, 0 = Stimme zur ganzen Kachel).
 * So zaehlen "Meine Aufgaben" und die Erinnerungen eine Stimme zu einem Entwurf von selbst mit.
 */
class m261022_100000_bildauswahl extends Migration
{
    public function safeUp()
    {
        $this->safeAddColumn('nexus_projekt_stimme', 'option_id', $this->integer()->notNull()->defaultValue(0));
        // Erst den neuen Schluessel anlegen, dann den alten entfernen -- der Fremdschluessel auf karte_id
        // braucht durchgehend einen Index
        $this->safeCreateIndex('ux-nexus_projekt_stimme-option', 'nexus_projekt_stimme', ['karte_id', 'runde', 'user_id', 'option_id'], true);
        $this->safeDropIndex('ux-nexus_projekt_stimme', 'nexus_projekt_stimme');

        $this->safeCreateTable('nexus_projekt_option', [
            'id' => $this->primaryKey(),
            'karte_id' => $this->integer()->notNull(),
            'datei_id' => $this->integer()->notNull(),
            'titel' => $this->string(120)->notNull(),
            'gewaehlt' => $this->boolean()->notNull()->defaultValue(false),
            'created_by' => $this->integer()->null(),
            'created_at' => $this->dateTime()->notNull(),
        ]);
        $this->safeCreateIndex('idx-nexus_projekt_option-karte', 'nexus_projekt_option', 'karte_id');
        $this->safeAddForeignKey('fk-nexus_projekt_option-karte', 'nexus_projekt_option', 'karte_id', 'nexus_projekt_karte', 'id', 'CASCADE');
        $this->safeAddForeignKey('fk-nexus_projekt_option-datei', 'nexus_projekt_option', 'datei_id', 'nexus_projekt_datei', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->safeDropTable('nexus_projekt_option');
        $this->delete('nexus_projekt_stimme', ['!=', 'option_id', 0]);
        $this->safeCreateIndex('ux-nexus_projekt_stimme', 'nexus_projekt_stimme', ['karte_id', 'runde', 'user_id'], true);
        $this->safeDropIndex('ux-nexus_projekt_stimme-option', 'nexus_projekt_stimme');
        $this->safeDropColumn('nexus_projekt_stimme', 'option_id');
    }
}
