<?php

use humhub\components\Migration;

/**
 * Unterhaltungen, die Benachrichtigung::senden angelegt hat, bekommen kein Antwortfeld mehr, sondern
 * Knoepfe ("In der Kachel antworten", "<Name> direkt schreiben") -- Mitglieder antworteten dort laufend,
 * die Antwort landete beim Assistenten statt bei der gemeinten Person (Josh, 04.11.2026).
 *
 * - nexus_benachrichtigung: welche Unterhaltung eine Benachrichtigung ist
 * - nexus_benachrichtigung_knopf: Knoepfe je Nachricht (JSON), nur fuer neue Nachrichten
 *
 * Bestand: alle Unterhaltungen, die der Assistent begonnen hat -- ausser Begruessung und Antworten
 * auf Erwaehnungen, dort ist Zurueckschreiben an Nova gewollt.
 */
class m261104_100000_benachrichtigungen extends Migration
{
    private const OHNE = [
        'Willkommen bei N.E.X.U.S. 🌱', 'Welcome to N.E.X.U.S. 🌱', 'Bienvenida/o a N.E.X.U.S. 🌱',
        'Добро пожаловать в N.E.X.U.S. 🌱', 'Bienvenue chez N.E.X.U.S. 🌱',
        'Deine Frage an Nova', 'Deine Frage an den N.E.X.U.S. Assistenten',
    ];

    public function safeUp()
    {
        $this->createTable('nexus_benachrichtigung', [
            'message_id' => $this->integer()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'PRIMARY KEY(message_id)',
        ]);
        $this->createTable('nexus_benachrichtigung_knopf', [
            'entry_id' => $this->integer()->notNull(),
            'message_id' => $this->integer()->notNull(),
            'knoepfe' => $this->text()->notNull(),
            'PRIMARY KEY(entry_id)',
        ]);
        $this->createIndex('idx-nexus_benachrichtigung_knopf-message', 'nexus_benachrichtigung_knopf', 'message_id');

        $bot = (int)getenv('ASSISTANT_USER_ID');
        if ($bot <= 0) {
            echo "ASSISTANT_USER_ID fehlt -- bestehende Unterhaltungen nicht markiert\n";
            return;
        }
        $ids = (new \yii\db\Query())->select('id')->from('message')
            ->where(['created_by' => $bot])->andWhere(['not in', 'title', self::OHNE])->column($this->db);
        $jetzt = date('Y-m-d H:i:s');
        foreach ($ids as $id) {
            $this->db->createCommand()->insert('nexus_benachrichtigung', ['message_id' => (int)$id, 'created_at' => $jetzt])->execute();
        }
        echo count($ids) . " bestehende Benachrichtigungs-Unterhaltungen markiert\n";
    }

    public function safeDown()
    {
        $this->dropTable('nexus_benachrichtigung_knopf');
        $this->dropTable('nexus_benachrichtigung');
    }
}
