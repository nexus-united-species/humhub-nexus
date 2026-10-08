<?php

use humhub\components\Migration;

class m260909_150000_init extends Migration
{
    public function safeUp()
    {
        $this->createTable('nexus_meeting', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'message' => $this->text()->notNull(),
            // Wiederholend: recurrence_weekday (1=Montag..7=Sonntag, ISO) +
            // recurrence_time (HH:MM, Europe/Berlin) gesetzt, event_date leer.
            // Einmalig: event_date gesetzt, die beiden anderen leer.
            'recurrence_weekday' => $this->tinyInteger(),
            'recurrence_time' => $this->string(5),
            'event_date' => $this->dateTime(),
            'active' => $this->boolean()->notNull()->defaultValue(true),
            'last_posted_at' => $this->dateTime(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        // Ein Meeting kann mehrere Ziel-Spaces haben (z.B. Kreis-2-Meeting
        // wird in Kreis 1 UND Kreis 2 gepostet).
        $this->createTable('nexus_meeting_space', [
            'id' => $this->primaryKey(),
            'meeting_id' => $this->integer()->notNull(),
            'space_id' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx-nexus_meeting_space-meeting_id', 'nexus_meeting_space', 'meeting_id');
        $this->addForeignKey(
            'fk-nexus_meeting_space-meeting_id',
            'nexus_meeting_space',
            'meeting_id',
            'nexus_meeting',
            'id',
            'CASCADE'
        );

        // Generischer Zeitstempel-Speicher fuer wiederkehrende Aktionen ohne
        // eigene Tabelle (Spendenaufruf, Selbstvorstellung, Wochenbericht) --
        // genau das Muster, das der Telegram-Bot mit seiner task_db nutzt.
        $this->createTable('nexus_reminder_state', [
            'name' => $this->string(64)->notNull(),
            'last_posted_at' => $this->dateTime()->notNull(),
        ]);
        $this->addPrimaryKey('pk-nexus_reminder_state', 'nexus_reminder_state', 'name');

        // Atomarer Dopplungsschutz fuer Direktfrage-Antworten -- dieselbe
        // Mentioning-Zeile darf nie zweimal eine Antwort auslösen (analog zum
        // message_id-Schutz der Ko-fi-Bruecke).
        $this->createTable('nexus_mention_reply', [
            'mentioning_id' => $this->integer()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
        ]);
        $this->addPrimaryKey('pk-nexus_mention_reply', 'nexus_mention_reply', 'mentioning_id');
    }

    public function safeDown()
    {
        $this->dropTable('nexus_mention_reply');
        $this->dropTable('nexus_reminder_state');
        $this->dropTable('nexus_meeting_space');
        $this->dropTable('nexus_meeting');
    }
}
