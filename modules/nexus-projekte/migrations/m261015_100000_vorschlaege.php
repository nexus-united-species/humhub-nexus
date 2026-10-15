<?php

use humhub\components\Migration;

/**
 * Vorschlaege (15.10.2026): eine Kachel kann ein Vorschlag sein -- mit Zusammenfassung, Begruendung,
 * Textaenderungen (Stelle / bisher / neu) und angehaengten Dateien in Fassungen. Ueber Vorschlaege wird
 * nach der Regel des Projekts entschieden (Konsent, Mehrheit oder einfache Rueckmeldung), auf Wunsch
 * mit Weitergabe an ein weiteres Gremium (z. B. die DAO) und Vermerk, wo der Beschluss umgesetzt wurde.
 */
class m261015_100000_vorschlaege extends Migration
{
    public function safeUp()
    {
        // Regeln je Projekt; bestehende Projekte behalten die einfache Rueckmeldung
        $this->safeAddColumn('nexus_projekt', 'regel', $this->string(20)->notNull()->defaultValue('einfach'));
        $this->safeAddColumn('nexus_projekt', 'frist_tage', $this->integer()->notNull()->defaultValue(14));
        $this->safeAddColumn('nexus_projekt', 'weitergabe', $this->string(100)->null());

        $this->safeAddColumn('nexus_projekt_karte', 'art', $this->string(20)->notNull()->defaultValue('aufgabe'));
        $this->safeAddColumn('nexus_projekt_karte', 'zusammenfassung', $this->text()->null());
        $this->safeAddColumn('nexus_projekt_karte', 'begruendung', $this->text()->null());
        $this->safeAddColumn('nexus_projekt_karte', 'ergebnis', $this->string(20)->null());          // angenommen | abgelehnt | zurueckgezogen
        $this->safeAddColumn('nexus_projekt_karte', 'ergebnis_text', $this->text()->null());         // z. B. "12 Zustimmungen, 0 Einwände"
        $this->safeAddColumn('nexus_projekt_karte', 'ergebnis_am', $this->dateTime()->null());
        $this->safeAddColumn('nexus_projekt_karte', 'weitergabe_status', $this->string(20)->null()); // vorgelegt | beschlossen | abgelehnt
        $this->safeAddColumn('nexus_projekt_karte', 'weitergabe_am', $this->date()->null());
        $this->safeAddColumn('nexus_projekt_karte', 'umgesetzt', $this->string(190)->null());        // z. B. "Bauplan V14"
        $this->safeAddColumn('nexus_projekt_karte', 'umgesetzt_am', $this->date()->null());
        // Runde, fuer die die Leitung schon gebeten wurde, das Ergebnis festzuhalten
        $this->safeAddColumn('nexus_projekt_karte', 'erinnert_auswertung', $this->integer()->notNull()->defaultValue(0));

        $this->safeCreateTable('nexus_projekt_aenderung', [
            'id' => $this->primaryKey(),
            'karte_id' => $this->integer()->notNull(),
            'stelle' => $this->string(255)->notNull(),
            'alt' => $this->text()->null(),
            'neu' => $this->text()->null(),
            'sortierung' => $this->integer()->notNull()->defaultValue(0),
        ]);
        $this->safeAddForeignKey('fk-nexus_projekt_aenderung-karte', 'nexus_projekt_aenderung', 'karte_id', 'nexus_projekt_karte', 'id', 'CASCADE');

        // Angehaengte Dateien liegen im Dateibereich des Kreises (cfiles) -- hier nur die Zuordnung + Fassung
        $this->safeCreateTable('nexus_projekt_datei', [
            'id' => $this->primaryKey(),
            'karte_id' => $this->integer()->notNull(),
            'cfile_id' => $this->integer()->notNull(),
            'fassung' => $this->integer()->notNull()->defaultValue(1),
            'hinweis' => $this->string(255)->null(),
            'created_by' => $this->integer()->null(),
            'created_at' => $this->dateTime()->notNull(),
        ]);
        $this->safeCreateIndex('idx-nexus_projekt_datei-karte', 'nexus_projekt_datei', 'karte_id');
        $this->safeAddForeignKey('fk-nexus_projekt_datei-karte', 'nexus_projekt_datei', 'karte_id', 'nexus_projekt_karte', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->safeDropTable('nexus_projekt_datei');
        $this->safeDropTable('nexus_projekt_aenderung');
        foreach (['art', 'zusammenfassung', 'begruendung', 'ergebnis', 'ergebnis_text', 'ergebnis_am', 'weitergabe_status',
                     'weitergabe_am', 'umgesetzt', 'umgesetzt_am', 'erinnert_auswertung'] as $spalte) {
            $this->safeDropColumn('nexus_projekt_karte', $spalte);
        }
        foreach (['regel', 'frist_tage', 'weitergabe'] as $spalte) {
            $this->safeDropColumn('nexus_projekt', $spalte);
        }
    }
}
