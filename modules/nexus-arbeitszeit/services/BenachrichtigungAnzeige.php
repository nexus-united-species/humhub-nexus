<?php

namespace nexus\modules\arbeitszeit\services;

use humhub\modules\mail\models\Message;
use humhub\modules\mail\models\MessageEntry;
use humhub\modules\user\models\User;
use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * Anzeige der Benachrichtigungs-Unterhaltungen (siehe Benachrichtigung): Knoepfe unter jeder Nachricht
 * und ein Hinweis statt des Antwortfelds. Das Nachrichten-Modul selbst bleibt unveraendert -- wir haengen
 * nur an die Ausgabe seiner Widgets an, das Antwortfeld blendet benachrichtigung.css aus.
 * Texte in der Sprache des Lesers.
 */
class BenachrichtigungAnzeige
{
    private const T = [
        'hinweis' => [
            'de' => '🔔 Das ist eine automatische Benachrichtigung. Hier kann man nicht antworten – was du hier schreiben würdest, liest kein Mensch. Nutze die Knöpfe bei der Nachricht.',
            'en' => '🔔 This is an automatic notification. You cannot reply here – nobody would read it. Please use the buttons next to the message.',
            'es' => '🔔 Esta es una notificación automática. Aquí no se puede responder: nadie lo leería. Usa los botones junto al mensaje.',
            'ru' => '🔔 Это автоматическое уведомление. Здесь нельзя ответить – этого никто не прочитает. Используйте кнопки у сообщения.',
            'fr' => '🔔 Ceci est une notification automatique. On ne peut pas répondre ici – personne ne le lirait. Utilise les boutons à côté du message.'],
        'nova' => [
            'de' => '✉️ Neue Nachricht an Nova', 'en' => '✉️ New message to Nova', 'es' => '✉️ Nuevo mensaje a Nova',
            'ru' => '✉️ Новое сообщение для Nova', 'fr' => '✉️ Nouveau message à Nova'],
        'nova_wozu' => [
            'de' => 'für Fragen an Nova oder um Arbeitsstunden einzutragen',
            'en' => 'to ask Nova something or to log working hours',
            'es' => 'para preguntar a Nova o anotar horas de trabajo',
            'ru' => 'чтобы задать вопрос Nova или записать рабочие часы',
            'fr' => 'pour poser une question à Nova ou noter des heures de travail'],
        'kachel' => ['de' => '📋 Zur Kachel', 'en' => '📋 Open the card', 'es' => '📋 Ir a la tarjeta', 'ru' => '📋 Открыть карточку', 'fr' => '📋 Ouvrir la carte'],
        'antworten' => [
            'de' => '💬 In der Kachel antworten', 'en' => '💬 Reply in the card', 'es' => '💬 Responder en la tarjeta',
            'ru' => '💬 Ответить в карточке', 'fr' => '💬 Répondre dans la carte'],
        'anfrage' => ['de' => '🆘 Zur Anfrage', 'en' => '🆘 Open the request', 'es' => '🆘 Ir a la solicitud', 'ru' => '🆘 Открыть запрос', 'fr' => '🆘 Ouvrir la demande'],
        'oeffnen' => ['de' => '🔗 Öffnen', 'en' => '🔗 Open', 'es' => '🔗 Abrir', 'ru' => '🔗 Открыть', 'fr' => '🔗 Ouvrir'],
        'person' => [
            'de' => '✉️ %s direkt schreiben', 'en' => '✉️ Write to %s directly', 'es' => '✉️ Escribir a %s directamente',
            'ru' => '✉️ Написать %s напрямую', 'fr' => '✉️ Écrire directement à %s'],
    ];

    /**
     * Je Anfrage gemerkt (eine Unterhaltung zeigt viele Nachrichten). An das Anfrage-Objekt gebunden,
     * nicht statisch: im Worker-Modus von FrankenPHP lebten statische Werte ueber viele Anfragen weiter.
     */
    private static ?\WeakMap $merker = null;

    public static function istBenachrichtigung(int $nachrichtId): bool
    {
        self::$merker ??= new \WeakMap();
        $anfrage = Yii::$app->request;
        $bekannt = self::$merker[$anfrage] ?? [];
        if (!array_key_exists($nachrichtId, $bekannt)) {
            $bekannt[$nachrichtId] = Yii::$app->db->getTableSchema('nexus_benachrichtigung') !== null
                && (bool)Yii::$app->db->createCommand('SELECT 1 FROM nexus_benachrichtigung WHERE message_id = :m', [':m' => $nachrichtId])->queryScalar();
            self::$merker[$anfrage] = $bekannt;
        }
        return $bekannt[$nachrichtId];
    }

    /** Knoepfe unter einer Nachricht (leer, wenn sie keine hat). */
    public static function knoepfe(MessageEntry $eintrag): string
    {
        if (!self::istBenachrichtigung((int)$eintrag->message_id)) {
            return '';
        }
        $json = Yii::$app->db->createCommand('SELECT knoepfe FROM nexus_benachrichtigung_knopf WHERE entry_id = :e', [':e' => (int)$eintrag->id])->queryScalar();
        $liste = $json ? (Json::decode($json) ?: []) : [];
        $html = '';
        foreach ($liste as $k) {
            $html .= self::knopf($k);
        }
        return $html === '' ? '' : Html::tag('div', $html, ['class' => 'nx-bn-knoepfe', 'data-nx-bn-eintrag' => (int)$eintrag->id]);
    }

    /** Markierung + Hinweis am Ende der Unterhaltung (leer bei normalen Unterhaltungen). */
    public static function hinweis(Message $nachricht): string
    {
        if (!self::istBenachrichtigung((int)$nachricht->id)) {
            return '';
        }
        $nova = User::findOne(['id' => (int)getenv('ASSISTANT_USER_ID'), 'status' => User::STATUS_ENABLED]);
        $knopf = $nova === null ? '' : Html::tag('div',
            self::modalKnopf(self::t('nova'), Url::to(['/mail/mail/create', 'userGuid' => $nova->guid]))
            . ' ' . Html::tag('span', Html::encode(self::t('nova_wozu')), ['class' => 'nx-bn-klein']),
            ['class' => 'nx-bn-zeile']);
        return Html::tag('span', '', ['class' => 'nx-bn-unterhaltung', 'hidden' => true])
            . Html::tag('div', Html::tag('p', Html::encode(self::t('hinweis'))) . $knopf, ['class' => 'nx-bn-hinweis']);
    }

    private static function knopf(array $k): string
    {
        if (!empty($k['person'])) {
            $mensch = User::findOne(['id' => (int)$k['person'], 'status' => User::STATUS_ENABLED]);
            if ($mensch === null || (int)$mensch->id === (int)Yii::$app->user->id) {
                return '';
            }
            return self::modalKnopf(sprintf(self::t('person'), $mensch->displayName), Url::to(['/mail/mail/create', 'userGuid' => $mensch->guid]));
        }
        $url = (string)($k['link'] ?? '');
        if (!preg_match('#^(https?://|/)#', $url)) {
            return '';
        }
        $art = in_array($k['art'] ?? '', ['kachel', 'antworten', 'anfrage'], true) ? $k['art'] : 'oeffnen';
        return Html::a(Html::encode(self::t($art)), $url, ['class' => 'btn btn-sm btn-primary nx-bn-knopf', 'data-pjax-prevent' => 1]);
    }

    /** Neue Nachricht im Fenster des Nachrichten-Moduls (dessen eigener Weg, wie "Nachricht senden" im Profil). */
    private static function modalKnopf(string $text, string $url): string
    {
        return Html::a(Html::encode($text), '#', [
            'class' => 'btn btn-sm btn-light nx-bn-knopf',
            'data-action-click' => 'ui.modal.load',
            'data-action-url' => $url,
        ]);
    }

    private static function t(string $schluessel): string
    {
        $sprache = strtolower(substr((string)Yii::$app->language, 0, 2));
        return self::T[$schluessel][$sprache] ?? self::T[$schluessel]['en'];
    }
}
