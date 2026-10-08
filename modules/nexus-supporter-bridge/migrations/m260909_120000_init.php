<?php

use humhub\components\Migration;

/**
 * Legt die zwei Tabellen des Moduls an:
 *  - nexus_supporter: ein Datensatz PRO Unterstuetzer:in (Ko-fi-E-Mail ist
 *    eindeutig), wird bei jeder Zahlung aktualisiert statt neu angelegt.
 *  - nexus_supporter_event: schlankes Audit-Protokoll, keine Zahlungs-
 *    details, nur wer/was/wann.
 */
class m260909_120000_init extends Migration
{
    public function safeUp()
    {
        $this->createTable('nexus_supporter', [
            'id' => $this->primaryKey(),
            'humhub_user_id' => $this->integer()->null(),
            'ko_fi_email' => $this->string(255)->notNull(),
            'ko_fi_message_id' => $this->string(128)->null(),
            'support_type' => $this->string(32)->notNull()->defaultValue('unknown'),
            'amount' => $this->decimal(10, 2)->null(),
            'currency' => $this->string(8)->null(),
            'membership_tier' => $this->string(128)->null(),
            'started_at' => $this->dateTime()->null(),
            'last_payment_at' => $this->dateTime()->null(),
            'expires_at' => $this->dateTime()->null(),
            'status' => $this->string(32)->notNull()->defaultValue('UNMATCHED'),
            // Josh's Schema (Abschnitt 1) listet dieses Feld nicht explizit,
            // aber Abschnitt 7 verlangt "Grund der Freischaltung
            // dokumentieren" -- ein Feld deckt Freischaltungs- UND
            // Entzugsgrund ab, spart eine zweite Spalte.
            'note' => $this->string(255)->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex('idx-nexus_supporter-ko_fi_email', 'nexus_supporter', 'ko_fi_email', true);
        $this->createIndex('idx-nexus_supporter-humhub_user_id', 'nexus_supporter', 'humhub_user_id');
        $this->createIndex('idx-nexus_supporter-status', 'nexus_supporter', 'status');

        $this->createTable('nexus_supporter_event', [
            'id' => $this->primaryKey(),
            'humhub_user_id' => $this->integer()->null(),
            'event_type' => $this->string(64)->notNull(),
            'reference' => $this->string(128)->null(),
            'created_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex('idx-nexus_supporter_event-reference', 'nexus_supporter_event', 'reference');
        $this->createIndex('idx-nexus_supporter_event-humhub_user_id', 'nexus_supporter_event', 'humhub_user_id');
        $this->createIndex('idx-nexus_supporter_event-event_type', 'nexus_supporter_event', 'event_type');
    }

    public function safeDown()
    {
        $this->dropTable('nexus_supporter_event');
        $this->dropTable('nexus_supporter');
    }
}
