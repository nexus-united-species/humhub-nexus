<?php

namespace nexus\modules\arbeitszeit\services;

use humhub\modules\mail\models\Message;
use humhub\modules\mail\models\MessageEntry;
use humhub\modules\mail\models\UserMessage;
use humhub\modules\user\models\User;
use Throwable;
use Yii;

/**
 * Benachrichtigt ueber private Nachrichten des Assistenten -- die kennen die Mitglieder schon.
 * Je Mensch EINE fortlaufende Unterhaltung pro Titel ("⏱ Arbeitsstunden zur Freigabe" bzw.
 * "⏱ Deine Arbeitsstunden"), nicht fuer jeden Eintrag eine neue -- sonst liefe das Postfach der
 * Admins voll. Ein fehlgeschlagener Versand darf das Eintragen/Freigeben nie verhindern.
 */
class Benachrichtigung
{
    public const TITEL_ADMIN = '⏱ Arbeitsstunden zur Freigabe';
    public const TITEL_MITGLIED = '⏱ Deine Arbeitsstunden';

    public static function senden(int $empfaengerId, string $titel, string $text): void
    {
        $botId = (int)getenv('ASSISTANT_USER_ID');
        $bot = $botId > 0 ? User::findOne(['id' => $botId]) : null;
        $empfaenger = User::findOne(['id' => $empfaengerId, 'status' => User::STATUS_ENABLED]);
        if ($bot === null || $empfaenger === null || $empfaengerId === $botId) {
            return;
        }

        // Der Absender muss beim Speichern der angemeldete Benutzer sein (HumHub setzt created_by
        // daraus). Danach UNBEDINGT den eigentlichen Menschen zurueck -- sonst liefe der Rest der
        // Anfrage (z. B. die Freigabe-Seite) als Assistent weiter.
        $vorher = Yii::$app->user->getIdentity(false);
        try {
            Yii::$app->user->setIdentity($bot);
            $nachricht = self::bestehendeUnterhaltung($titel, $bot->id, $empfaenger->id);
            if ($nachricht === null) {
                $nachricht = new Message(['title' => $titel]);
                if (!$nachricht->save()) {
                    throw new \RuntimeException(json_encode($nachricht->errors));
                }
                $nachricht->addRecepient($empfaenger, false, true);
                $nachricht->addRecepient($bot, true, false);
            }
            $eintrag = MessageEntry::createForMessage($nachricht, $bot, $text);
            if (!$eintrag->save()) {
                throw new \RuntimeException(json_encode($eintrag->errors));
            }
            $eintrag->notify(true);
        } catch (Throwable $e) {
            Yii::error('nexus-arbeitszeit: Benachrichtigung an ' . $empfaengerId . ' fehlgeschlagen: ' . $e->getMessage(), 'nexus-arbeitszeit');
        } finally {
            Yii::$app->user->setIdentity($vorher);
        }
    }

    private static function bestehendeUnterhaltung(string $titel, int $botId, int $empfaengerId): ?Message
    {
        $id = Message::find()
            ->alias('m')
            ->innerJoin(['a' => UserMessage::tableName()], 'a.message_id = m.id AND a.user_id = :bot', [':bot' => $botId])
            ->innerJoin(['b' => UserMessage::tableName()], 'b.message_id = m.id AND b.user_id = :empf', [':empf' => $empfaengerId])
            ->where(['m.title' => $titel])
            ->orderBy(['m.id' => SORT_DESC])
            ->select('m.id')
            ->scalar();
        return $id ? Message::findOne($id) : null;
    }

    /** Adresse einer Seite des Moduls als voller Link (auch in Nachrichten klickbar). */
    public static function link(string $pfad): string
    {
        $basis = rtrim((string)Yii::$app->settings->get('baseUrl'), '/');
        return $basis . $pfad;
    }
}
