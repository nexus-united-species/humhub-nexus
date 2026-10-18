<?php

use humhub\components\Migration;

/**
 * Redaktion im Portal: Kategorien (Themen) und eigene Sammlungen in der Datenbank statt nur in den
 * Einstellungen, Artikel als Entwurf.
 */
class m261018_100000_redaktion extends Migration
{
    public function safeUp()
    {
        // Neue Artikel aus dem Portal beginnen als Entwurf; alles Bestehende ist veroeffentlicht.
        $this->safeAddColumn('nexus_wissen_artikel', 'entwurf', $this->boolean()->notNull()->defaultValue(false));
        $this->safeAddColumn('nexus_wissen_artikel', 'created_by', $this->integer()->null());

        // Kategorien je Sammlung. Beim ersten Aufruf aus den Einstellungen ("themen") und den Themen
        // der vorhandenen Artikel befuellt (services/Themen.php), danach im Portal gepflegt.
        $this->safeCreateTable('nexus_wissen_thema', [
            'id' => $this->primaryKey(),
            'sammlung' => $this->string(64)->notNull(),
            // steht so in nexus_wissen_artikel.thema -- aendert sich beim Umbenennen nicht
            'code' => $this->string(100)->notNull(),
            // Text oder JSON {"de": ..., "en": ..., "es": ...}
            'name' => $this->text()->notNull(),
            'symbol' => $this->string(40)->notNull()->defaultValue('book'),
            'sortierung' => $this->integer()->notNull()->defaultValue(0),
        ]);
        $this->safeCreateIndex('ux-nexus_wissen_thema', 'nexus_wissen_thema', ['sammlung', 'code'], true);

        // Sammlungen, die Kreisbesitzer im Portal selbst anlegen (die aus common.php bleiben dort).
        $this->safeCreateTable('nexus_wissen_sammlung', [
            'id' => $this->primaryKey(),
            'kennung' => $this->string(64)->notNull(),
            'kreis_id' => $this->integer()->notNull(),
            'name' => $this->string(120)->notNull(),
            'beschreibung' => $this->string(255)->null(),
            'symbol' => $this->string(40)->notNull()->defaultValue('book'),
            'hinweis' => $this->text()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'created_by' => $this->integer()->null(),
        ]);
        $this->safeCreateIndex('ux-nexus_wissen_sammlung-kennung', 'nexus_wissen_sammlung', 'kennung', true);
        $this->safeCreateIndex('idx-nexus_wissen_sammlung-kreis', 'nexus_wissen_sammlung', 'kreis_id');
    }

    public function safeDown()
    {
        $this->safeDropTable('nexus_wissen_sammlung');
        $this->safeDropTable('nexus_wissen_thema');
        $this->safeDropColumn('nexus_wissen_artikel', 'created_by');
        $this->safeDropColumn('nexus_wissen_artikel', 'entwurf');
    }
}
