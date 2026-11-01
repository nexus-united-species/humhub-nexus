<?php

use humhub\components\Migration;

/**
 * Stillgelegte Projekte (01.11.2026): Ein Projekt wird neu aufgesetzt, das alte bleibt lesbar. Es zeigt auf das
 * Projekt, in dem es weitergeht. Der Stand selbst braucht keine neue Spalte.
 */
class m261101_100000_stillgelegt extends Migration
{
    public function safeUp()
    {
        $this->safeAddColumn('nexus_projekt', 'nachfolger_id', $this->integer()->null());
    }

    public function safeDown()
    {
        $this->safeDropColumn('nexus_projekt', 'nachfolger_id');
    }
}
