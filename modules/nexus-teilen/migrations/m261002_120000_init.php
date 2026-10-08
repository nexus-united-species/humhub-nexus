<?php

use humhub\components\Migration;

/**
 * Eine Zeile je oeffentlich lesbarem Inhalt (Beitrag oder Wiki-Seite). Gibt es keine Zeile,
 * ist der Inhalt intern -- das ist der Normalfall. Der Schluessel im Link ist zufaellig und
 * nicht die Inhalts-Nummer: sonst koennte man durch Hochzaehlen nach Freigaben suchen.
 */
class m261002_120000_init extends Migration
{
    public function safeUp()
    {
        $this->createTable('nexus_teilen_freigabe', [
            'content_id' => $this->integer()->notNull(),
            'schluessel' => $this->string(32)->notNull(),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'PRIMARY KEY (content_id)',
        ]);
        $this->createIndex('ux_nexus_teilen_schluessel', 'nexus_teilen_freigabe', 'schluessel', true);
        // Wird der Beitrag geloescht, verschwindet auch die Freigabe.
        $this->addForeignKey('fk_nexus_teilen_content', 'nexus_teilen_freigabe', 'content_id', 'content', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('nexus_teilen_freigabe');
    }
}
