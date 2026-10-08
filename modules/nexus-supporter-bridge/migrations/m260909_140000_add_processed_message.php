<?php

use humhub\components\Migration;

/**
 * Ergaenzt die zuerst eingebaute, rein anwendungsseitige Dopplungspruefung
 * um eine echte, atomare Sperre auf Datenbankebene (message_id als PRIMARY
 * KEY). Hintergrund: ein reines "erst nachsehen, dann entscheiden" hat ein
 * Zeitfenster, in dem zwei fast gleichzeitig eintreffende Zustellungen
 * derselben message_id beide durchrutschen koennten. Der Einfuege-Versuch
 * selbst ist jetzt die Sperre.
 */
class m260909_140000_add_processed_message extends Migration
{
    public function safeUp()
    {
        $this->createTable('nexus_supporter_processed_message', [
            'message_id' => $this->string(128)->notNull(),
            'created_at' => $this->dateTime()->notNull(),
        ]);

        $this->addPrimaryKey('pk-nexus_supporter_processed_message', 'nexus_supporter_processed_message', 'message_id');
    }

    public function safeDown()
    {
        $this->dropTable('nexus_supporter_processed_message');
    }
}
