<?php

use humhub\components\Migration;

/**
 * Ein Datensatz je Eintrag. status: entwurf (Assistent wartet noch auf den Kreis), offen (wartet auf
 * Freigabe), freigegeben, ruecksprache. Geloescht wird nur ein eigener, noch offener Eintrag.
 */
class m260928_120000_init extends Migration
{
    public function safeUp()
    {
        $this->createTable('nexus_arbeitszeit', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'space_id' => $this->integer()->null(),
            'stunden' => $this->decimal(5, 2)->notNull(),
            'datum' => $this->date()->notNull(),
            'beschreibung' => $this->text()->notNull(),
            'status' => $this->string(16)->notNull()->defaultValue('offen'),
            'quelle' => $this->string(16)->notNull()->defaultValue('formular'),
            'geprueft_von' => $this->integer()->null(),
            'geprueft_am' => $this->dateTime()->null(),
            'rueckfrage' => $this->string(500)->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex('idx-nexus_arbeitszeit-user', 'nexus_arbeitszeit', 'user_id');
        $this->createIndex('idx-nexus_arbeitszeit-status', 'nexus_arbeitszeit', 'status');
        $this->createIndex('idx-nexus_arbeitszeit-datum', 'nexus_arbeitszeit', 'datum');
    }

    public function safeDown()
    {
        $this->dropTable('nexus_arbeitszeit');
    }
}
