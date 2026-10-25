<?php

use humhub\components\Migration;

/**
 * Finanzierungs-Ampel. Alle Betraege in CENT (ganze Zahlen), damit nie Rundungsfehler entstehen.
 */
class m261026_100000_init extends Migration
{
    public function safeUp()
    {
        // Kostenkachel: laufend (jeden Monat) oder einmalig; bereich grund|optional|entwicklung
        $this->createTable('nexus_fin_kachel', [
            'id' => $this->primaryKey(),
            'bereich' => $this->string(12)->notNull(),
            'gruppe' => $this->string(80)->null(),
            'titel' => $this->string(120)->notNull(),
            'beschreibung' => $this->text()->null(),
            'art' => $this->string(10)->notNull()->defaultValue('laufend'),
            'betrag' => $this->integer()->notNull()->defaultValue(0),
            'einordnung' => $this->string(14)->notNull()->defaultValue('geschaetzt'),
            'freigegeben' => $this->boolean()->notNull()->defaultValue(0),
            'zusatz' => $this->string(14)->null(),
            'rang' => $this->integer()->notNull()->defaultValue(100),
            'oeffentlich' => $this->boolean()->notNull()->defaultValue(1),
            'beguenstigt_id' => $this->integer()->null(),
            'created_by' => $this->integer()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex('idx-nexus_fin_kachel-bereich', 'nexus_fin_kachel', ['bereich', 'rang']);

        // Eingang: frei (zweck_kachel_id NULL) oder zweckgebunden; Ko-fi mit eindeutiger Kennung
        $this->createTable('nexus_fin_eingang', [
            'id' => $this->primaryKey(),
            'datum' => $this->date()->notNull(),
            'betrag' => $this->integer()->notNull(),
            'waehrung' => $this->string(3)->notNull()->defaultValue('EUR'),
            'quelle' => $this->string(14)->notNull(),
            'zweck_kachel_id' => $this->integer()->null(),
            'referenz' => $this->string(190)->null(),
            'notiz' => $this->string(255)->null(),
            'kofi_id' => $this->string(128)->null(),
            'erfasst_von' => $this->integer()->null(),
            'created_at' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex('idx-nexus_fin_eingang-kofi', 'nexus_fin_eingang', 'kofi_id', true);
        $this->createIndex('idx-nexus_fin_eingang-datum', 'nexus_fin_eingang', 'datum');

        // Zuweisung: Geld fuer eine Kachel (laufend: fuer einen Monat; einmalig: monat NULL)
        $this->createTable('nexus_fin_zuweisung', [
            'id' => $this->primaryKey(),
            'kachel_id' => $this->integer()->notNull(),
            'monat' => $this->string(7)->null(),
            'betrag' => $this->integer()->notNull(),
            'eingang_id' => $this->integer()->null(),
            'verteilung_id' => $this->integer()->null(),
            'created_by' => $this->integer()->null(),
            'created_at' => $this->dateTime()->notNull(),
        ]);
        $this->addForeignKey('fk-nexus_fin_zuweisung-kachel', 'nexus_fin_zuweisung', 'kachel_id', 'nexus_fin_kachel', 'id', 'CASCADE');
        $this->createIndex('idx-nexus_fin_zuweisung-monat', 'nexus_fin_zuweisung', ['kachel_id', 'monat']);

        // Ausgabe: tatsaechlich bezahlt; bei Verguetung erst nach Freigabe durch eine zweite Person gezaehlt
        $this->createTable('nexus_fin_ausgabe', [
            'id' => $this->primaryKey(),
            'kachel_id' => $this->integer()->notNull(),
            'monat' => $this->string(7)->null(),
            'datum' => $this->date()->notNull(),
            'betrag' => $this->integer()->notNull(),
            'notiz' => $this->string(255)->null(),
            'beleg' => $this->string(190)->null(),
            'status' => $this->string(12)->notNull()->defaultValue('freigegeben'),
            'erfasst_von' => $this->integer()->null(),
            'freigegeben_von' => $this->integer()->null(),
            'created_at' => $this->dateTime()->notNull(),
        ]);
        $this->addForeignKey('fk-nexus_fin_ausgabe-kachel', 'nexus_fin_ausgabe', 'kachel_id', 'nexus_fin_kachel', 'id', 'CASCADE');

        // Verteilung aus dem Topf: Vorschlag -> (zweite Freigabe) -> gebucht
        $this->createTable('nexus_fin_verteilung', [
            'id' => $this->primaryKey(),
            'monat' => $this->string(7)->notNull(),
            'vorschlag' => $this->text()->notNull(),
            'summe' => $this->integer()->notNull(),
            'status' => $this->string(14)->notNull()->defaultValue('vorgeschlagen'),
            'vorgeschlagen_von' => $this->integer()->null(),
            'bestaetigt_von' => $this->integer()->null(),
            'zweite_von' => $this->integer()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        // Monatsrueckblick in einfachen Worten, fuer alle sichtbar
        $this->createTable('nexus_fin_rueckblick', [
            'monat' => $this->string(7)->notNull(),
            'text' => $this->text()->notNull(),
            'von' => $this->integer()->null(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->addPrimaryKey('pk-nexus_fin_rueckblick', 'nexus_fin_rueckblick', 'monat');
    }

    public function safeDown()
    {
        foreach (['nexus_fin_rueckblick', 'nexus_fin_verteilung', 'nexus_fin_ausgabe', 'nexus_fin_zuweisung', 'nexus_fin_eingang', 'nexus_fin_kachel'] as $t) {
            $this->dropTable($t);
        }
    }
}
