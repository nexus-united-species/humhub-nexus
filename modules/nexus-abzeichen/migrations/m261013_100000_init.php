<?php

use humhub\components\Migration;

class m261013_100000_init extends Migration
{
    public function safeUp()
    {
        // Wer sich zu welchem Abzeichen bekennt. Bewusst OHNE Datum: kein "wer war zuerst",
        // keine Hierarchie (z. B. Genesis-Pakt: "Wer ihn frueher gesprochen hat, steht nicht ueber dem,
        // der ihn spaeter spricht"). Loesen = Zeile loeschen, es bleibt keine Spur.
        $this->safeCreateTable('nexus_abzeichen_bekenntnis', [
            'user_id' => $this->integer()->notNull(),
            'abzeichen' => $this->string(64)->notNull(),
        ]);
        $this->safeAddPrimaryKey('pk-nexus_abzeichen_bekenntnis', 'nexus_abzeichen_bekenntnis', ['user_id', 'abzeichen']);
        $this->safeAddForeignKey('fk-nexus_abzeichen_bekenntnis-user', 'nexus_abzeichen_bekenntnis', 'user_id', 'user', 'id', 'CASCADE');

        // Aktive Minuten je Mensch und Kalenderwoche (z. B. "2026-W42"); nach einigen Wochen geloescht
        $this->safeCreateTable('nexus_abzeichen_aktiv', [
            'user_id' => $this->integer()->notNull(),
            'woche' => $this->string(8)->notNull(),
            'minuten' => $this->integer()->notNull()->defaultValue(0),
            'zuletzt' => $this->dateTime()->notNull(),
            'erreicht' => $this->string(255)->notNull()->defaultValue(''), // Kennungen der Momente, die diese Woche schon gefeiert wurden
        ]);
        $this->safeAddPrimaryKey('pk-nexus_abzeichen_aktiv', 'nexus_abzeichen_aktiv', ['user_id', 'woche']);
        $this->safeAddForeignKey('fk-nexus_abzeichen_aktiv-user', 'nexus_abzeichen_aktiv', 'user_id', 'user', 'id', 'CASCADE');

        // Wie oft ein Moment erreicht wurde (nur fuer den Menschen selbst sichtbar) und ob die Feier noch aussteht
        $this->safeCreateTable('nexus_abzeichen_moment', [
            'user_id' => $this->integer()->notNull(),
            'moment' => $this->string(64)->notNull(),
            'anzahl' => $this->integer()->notNull()->defaultValue(0),
            'feier_offen' => $this->boolean()->notNull()->defaultValue(false),
        ]);
        $this->safeAddPrimaryKey('pk-nexus_abzeichen_moment', 'nexus_abzeichen_moment', ['user_id', 'moment']);
        $this->safeAddForeignKey('fk-nexus_abzeichen_moment-user', 'nexus_abzeichen_moment', 'user_id', 'user', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        foreach (['nexus_abzeichen_moment', 'nexus_abzeichen_aktiv', 'nexus_abzeichen_bekenntnis'] as $t) {
            $this->safeDropTable($t);
        }
    }
}
