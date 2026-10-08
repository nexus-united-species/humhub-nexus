<?php

namespace nexus\modules\communityAssistant\services;

use humhub\modules\content\widgets\richtext\converter\RichTextToPlainTextConverter;
use humhub\modules\mail\models\AbstractMessageEntry;
use humhub\modules\mail\models\MessageEntry;
use humhub\modules\mail\models\UserMessage;
use humhub\modules\user\models\User;
use Yii;

/**
 * Direktnachricht an den Assistenten: HumHubs @-Erwaehnungs-Erkennung
 * unterstuetzt private Nachrichten nicht (nur Beitraege/Kommentare, siehe
 * MentioningExtension::onPostProcess -- geprueft im Quellcode). Deshalb ein
 * eigener, einfacherer Weg: JEDE Nachricht in einer Konversation, an der
 * der Assistent beteiligt ist und die nicht von ihm selbst stammt, gilt
 * als Frage und wird beantwortet. Kein @-Tag noetig (Vorgabe Josh
 * 09.09.2026).
 *
 * Bewusst nur bei GENAU zwei Teilnehmern (Mensch + Assistent) -- in einer
 * Gruppenkonversation mit mehreren Menschen waere unklar, ob die Nachricht
 * ueberhaupt an den Assistenten gerichtet war.
 */
class DirectMessageReplyService
{
    public function verarbeiten(MessageEntry $entry): void
    {
        if ((int)$entry->type !== AbstractMessageEntry::TYPE_MESSAGE) {
            return; // Systemeintrag (z.B. "Nutzer beigetreten"), keine echte Nachricht
        }

        $assistentId = (int)getenv('ASSISTANT_USER_ID');
        if ($assistentId <= 0 || (int)$entry->created_by === $assistentId) {
            return;
        }

        $teilnehmerIds = UserMessage::find()
            ->where(['message_id' => $entry->message_id])
            ->select('user_id')
            ->column();

        if (count($teilnehmerIds) !== 2 || !in_array($assistentId, array_map('intval', $teilnehmerIds), true)) {
            return; // Assistent nicht beteiligt, oder mehr als 1:1
        }

        if (!$this->alsErstmaligMarkieren((int)$entry->id)) {
            return;
        }

        $frage = RichTextToPlainTextConverter::process((string)$entry->content);
        if (trim($frage) === '') {
            return;
        }

        // Arbeitsstunden in normalen Worten ("heute 2 Stunden am Newsletter gearbeitet") --
        // Modul nexus-arbeitszeit (seit 28.09.2026). Antwortet es selbst (nicht null), bekommt die
        // Nachricht keine zusaetzliche KI-Antwort. Ohne das Modul laeuft alles wie vorher.
        $erfassung = 'nexus\modules\arbeitszeit\services\AssistentErfassung';
        if (Yii::$app->hasModule('nexus-arbeitszeit') && class_exists($erfassung)) {
            $absender = User::findOne(['id' => (int)$entry->created_by]);
            $zeitAntwort = $absender ? $erfassung::verarbeiten($absender, $frage) : null;
            if ($zeitAntwort !== null) {
                (new PosterService($assistentId))->antwortenInKonversation((int)$entry->message_id, $zeitAntwort);
                return;
            }
        }

        try {
            $antwort = (new AiService())->frage($frage, AssistentPersona::systemAnweisung($frage));
        } catch (\Throwable $e) {
            Yii::error('nexus-community-assistant: KI-Antwort auf Direktnachricht fehlgeschlagen: ' . $e->getMessage());
            return;
        }

        [$antwort, $anMenschen] = Weitergabe::zerlegen($antwort);
        (new PosterService($assistentId))->antwortenInKonversation((int)$entry->message_id, $antwort);
        if ($anMenschen) {
            Weitergabe::anMenschen(User::findOne(['id' => (int)$entry->created_by]), $frage, $antwort, 'private Nachricht an Nova – die sehen nur die beiden');
        }
    }

    private function alsErstmaligMarkieren(int $messageEntryId): bool
    {
        try {
            Yii::$app->db->createCommand()->insert('nexus_message_reply', [
                'message_entry_id' => $messageEntryId,
                'created_at' => date('Y-m-d H:i:s'),
            ])->execute();
            return true;
        } catch (\yii\db\IntegrityException) {
            return false;
        }
    }
}
