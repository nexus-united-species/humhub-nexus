<?php

use humhub\components\Migration;

class m260909_180000_init extends Migration
{
    public function safeUp()
    {
        $this->createTable('nexus_book', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'author' => $this->string(255),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->createTable('nexus_book_chapter', [
            'id' => $this->primaryKey(),
            'book_id' => $this->integer()->notNull(),
            'sort_order' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
            // LONGTEXT statt TEXT: Kapitel mit eingebetteten Bildern als
            // Data-URIs koennen mehrere Megabyte gross werden.
            'html_content' => 'LONGTEXT NOT NULL',
        ]);
        $this->createIndex('idx-nexus_book_chapter-book_id', 'nexus_book_chapter', 'book_id');
        $this->addForeignKey(
            'fk-nexus_book_chapter-book_id',
            'nexus_book_chapter',
            'book_id',
            'nexus_book',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropTable('nexus_book_chapter');
        $this->dropTable('nexus_book');
    }
}
