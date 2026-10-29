<?php

namespace nexus\modules\communityAssistant\services;

use humhub\modules\content\widgets\richtext\converter\RichTextToPlainTextConverter;
use humhub\modules\mail\models\AbstractMessageEntry;
use humhub\modules\mail\models\Message;
use humhub\modules\mail\models\MessageEntry;
use humhub\modules\mail\models\UserMessage;
use humhub\modules\user\models\User;
use Yii;
use yii\db\Connection;

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
        // Beginnt ein Mitglied selbst eine NEUE Unterhaltung mit Nova, speichert das Mail-Modul die
        // erste Nachricht, bevor es den Absender als Teilnehmer eintraegt (CreateMessage::save, alles
        // in einer Transaktion). Nova sah dann nur sich selbst und hielt die Nachricht nicht fuer eine
        // Frage an sich -- sie blieb fuer immer unbeantwortet (Rueckmeldung eines Mitglieds, 17.10.2026).
        // Deshalb erst nach dem Abschluss der Transaktion antworten, dann sind alle Teilnehmer da.
        $db = Yii::$app->db;
        $transaktion = $db->getTransaction();
        if ($transaktion !== null && $transaktion->getIsActive()) {
            $nachher = null;
            $abbruch = null;
            $nachher = function () use ($db, $entry, &$nachher, &$abbruch): void {
                $db->off(Connection::EVENT_COMMIT_TRANSACTION, $nachher);
                $db->off(Connection::EVENT_ROLLBACK_TRANSACTION, $abbruch);
                $this->beantworten($entry);
            };
            $abbruch = function () use ($db, &$nachher, &$abbruch): void {
                $db->off(Connection::EVENT_COMMIT_TRANSACTION, $nachher);
                $db->off(Connection::EVENT_ROLLBACK_TRANSACTION, $abbruch);
            };
            $db->on(Connection::EVENT_COMMIT_TRANSACTION, $nachher);
            $db->on(Connection::EVENT_ROLLBACK_TRANSACTION, $abbruch);
            return;
        }
        $this->beantworten($entry);
    }

    public function beantworten(MessageEntry $entry): void
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
        $absender = User::findOne(['id' => (int)$entry->created_by]);

        // Arbeitsstunden in normalen Worten ("heute 2 Stunden am Newsletter gearbeitet") --
        // Modul nexus-arbeitszeit (seit 28.09.2026). Antwortet es selbst (nicht null), bekommt die
        // Nachricht keine zusaetzliche KI-Antwort. Ohne das Modul laeuft alles wie vorher.
        $erfassung = 'nexus\modules\arbeitszeit\services\AssistentErfassung';
        if (Yii::$app->hasModule('nexus-arbeitszeit') && class_exists($erfassung)) {
            $zeitAntwort = $absender ? $erfassung::verarbeiten($absender, $frage) : null;
            if ($zeitAntwort !== null) {
                (new PosterService($assistentId))->antwortenInKonversation((int)$entry->message_id, $zeitAntwort);
                return;
            }
        }

        // Automatische Benachrichtigungen (Projekt, Team, Finanzierung, neue Mitglieder, Wissen ...) sind
        // Unterhaltungen, die der Assistent selbst angelegt hat. Antwortet ein Mitglied dort -- meist auf die
        // Rueckmeldung, um die es geht --, ist das KEINE Frage an Nova. Eine KI-Antwort darauf wirkte, als
        // werde mitgelesen und kontrolliert (Meldung eines Mitglieds, 29.10.2026). Nur ein fester Hinweis,
        // wie man richtig antwortet. Die Willkommensnachricht (Titel endet auf 🌱) bleibt eine Gespraechs-
        // einladung, dort antwortet Nova weiter.
        if ($this->istSystemUnterhaltung((int)$entry->message_id, $assistentId)) {
            $this->systemHinweis($assistentId, $entry, $absender);
            return;
        }

        try {
            $antwort = (new AiService())->frage($frage, AssistentPersona::systemAnweisung($frage));
        } catch (\Throwable $e) {
            Yii::error('nexus-community-assistant: KI-Antwort auf Direktnachricht fehlgeschlagen: ' . $e->getMessage());
            // Nie still schweigen: Der Mensch erfaehrt, dass es nicht an ihm liegt, und ein Mensch
            // aus dem Support-Team uebernimmt die Frage.
            $this->stoerungMelden($assistentId, $entry, $absender, $frage);
            return;
        }

        [$antwort, $anMenschen] = Weitergabe::zerlegen($antwort);
        if (!$anMenschen) {
            // Testgruppe: Hinweis auf die ausfuehrliche Fassung (Nova – Gespraeche)
            $antwort .= Gespraeche::wegweiser($absender);
        }
        (new PosterService($assistentId))->antwortenInKonversation((int)$entry->message_id, $antwort);
        if ($anMenschen) {
            Weitergabe::anMenschen($absender, $frage, $antwort, 'private Nachricht an Nova – die sehen nur die beiden');
        }
    }

    private function istSystemUnterhaltung(int $nachrichtId, int $assistentId): bool
    {
        $nachricht = Message::findOne($nachrichtId);
        if ($nachricht === null || (int)$nachricht->created_by !== $assistentId) {
            return false;
        }
        return !str_ends_with(trim((string)$nachricht->title), '🌱');
    }

    /** Fester Hinweis (kein KI-Text), hoechstens einmal hintereinander je Unterhaltung. */
    private function systemHinweis(int $assistentId, MessageEntry $entry, ?User $absender): void
    {
        $vorher = MessageEntry::find()
            ->where(['message_id' => $entry->message_id, 'created_by' => $assistentId])
            ->andWhere(['<', 'id', $entry->id])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        if ($vorher !== null && str_starts_with(ltrim((string)$vorher->content), 'ℹ️')) {
            return;
        }
        $url = rtrim((string)Yii::$app->settings->get('baseUrl'), '/') . '/nexus-community-assistant/gespraech/index';
        $texte = [
            'de' => "ℹ️ Das ist eine automatische Benachrichtigung. Hier antwortet niemand, auch Nova nicht. "
                . "Wenn du auf eine Rückmeldung antworten möchtest, nutze den Knopf „Antworten“ direkt im Portal (der Link steht in der Nachricht oben). "
                . "Wenn du Nova etwas fragen möchtest, nimm [Nova – Gespräche](%s) oder schreib ihr eine eigene Nachricht.",
            'en' => "ℹ️ This is an automatic notification. Nobody answers here, not even Nova. "
                . "To reply to a comment, use the “Reply” button directly in the portal (the link is in the message above). "
                . "If you want to ask Nova something, use [Nova – Conversations](%s) or write her a message of your own.",
            'es' => "ℹ️ Esto es una notificación automática. Aquí no responde nadie, tampoco Nova. "
                . "Para responder a un comentario, usa el botón «Responder» directamente en el portal (el enlace está en el mensaje de arriba). "
                . "Si quieres preguntarle algo a Nova, usa [Nova – Conversaciones](%s) o escríbele un mensaje propio.",
            'ru' => "ℹ️ Это автоматическое уведомление. Здесь никто не отвечает, в том числе и Нова. "
                . "Чтобы ответить на отзыв, используйте кнопку «Ответить» прямо в портале (ссылка — в сообщении выше). "
                . "Если хотите о чём-то спросить Нову, воспользуйтесь разделом [Нова — беседы](%s) или напишите ей отдельное сообщение.",
            'fr' => "ℹ️ Ceci est une notification automatique. Personne ne répond ici, pas même Nova. "
                . "Pour répondre à un retour, utilise le bouton « Répondre » directement dans le portail (le lien se trouve dans le message ci-dessus). "
                . "Si tu veux poser une question à Nova, utilise [Nova – Conversations](%s) ou écris-lui un message à part.",
        ];
        $sprache = substr((string)($absender->language ?? 'de'), 0, 2);
        try {
            (new PosterService($assistentId))->antwortenInKonversation(
                (int)$entry->message_id,
                sprintf($texte[$sprache] ?? $texte['de'], $url)
            );
        } catch (\Throwable $e) {
            Yii::error('nexus-community-assistant: Systemhinweis fehlgeschlagen: ' . $e->getMessage());
        }
    }

    private function stoerungMelden(int $assistentId, MessageEntry $entry, ?User $absender, string $frage): void
    {
        $texte = [
            'de' => "Entschuldige – ich konnte deine Frage gerade wegen einer technischen Störung nicht beantworten. "
                . "Ich habe sie an das Support-Team weitergegeben, ein Mensch meldet sich bei dir.\n\nNova (KI)",
            'en' => "Sorry – I couldn't answer your question right now because of a technical problem. "
                . "I've passed it on to the support team, a person will get back to you.\n\nNova (AI)",
            'es' => "Lo siento: no he podido responder a tu pregunta por un problema técnico. "
                . "La he pasado al equipo de soporte; una persona se pondrá en contacto contigo.\n\nNova (IA)",
            'ru' => "Прости — сейчас я не смогла ответить на твой вопрос из-за технической неполадки. "
                . "Я передала его команде поддержки, человек свяжется с тобой.\n\nНова (ИИ)",
            'fr' => "Désolée – je n’ai pas pu répondre à ta question à l’instant à cause d’un problème technique. "
                . "Je l’ai transmise à l’équipe d’assistance, une personne te répondra.\n\nNova (IA)",
        ];
        $sprache = substr((string)($absender->language ?? 'de'), 0, 2);
        try {
            (new PosterService($assistentId))->antwortenInKonversation((int)$entry->message_id, $texte[$sprache] ?? $texte['de']);
            Weitergabe::anMenschen($absender, $frage, '(keine Antwort von Nova – technische Störung)', 'private Nachricht an Nova – die sehen nur die beiden');
        } catch (\Throwable $e) {
            Yii::error('nexus-community-assistant: Stoerungsmeldung fehlgeschlagen: ' . $e->getMessage());
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
