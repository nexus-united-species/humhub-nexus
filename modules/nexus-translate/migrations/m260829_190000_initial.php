<?php

use yii\db\Migration;

class m260829_190000_initial extends Migration
{
    public function safeUp()
    {
        $this->createTable('nexus_translation_cache', [
            'post_id' => $this->integer()->notNull(),
            // Sprachcode statt fester Auswahl -- HumHub kennt weit mehr
            // Sprachen als wir vorab aufzaehlen wollen.
            'language' => $this->string(10)->notNull(),
            'translated_text' => $this->text()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
        ]);
        $this->addPrimaryKey('pk_nexus_translation_cache', 'nexus_translation_cache', ['post_id', 'language']);
        $this->addForeignKey(
            'fk_nexus_translation_cache_post_id',
            'nexus_translation_cache',
            'post_id',
            'post',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropTable('nexus_translation_cache');
    }
}
