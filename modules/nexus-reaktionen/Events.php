<?php

namespace nexus\modules\reaktionen;

use humhub\modules\mail\models\AbstractMessageEntry;
use nexus\modules\reaktionen\assets\ReaktionenAsset;
use nexus\modules\reaktionen\services\Nachrichten;
use Throwable;
use Yii;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;

class Events
{
    public static function onLayoutAddonInit($event): void
    {
        try {
            if (Yii::$app->user->isGuest) {
                return;
            }
            /** @var Module $modul */
            $modul = Yii::$app->getModule('nexus-reaktionen');
            $konfig = [
                'emojis' => array_values($modul->emojis),
                'urlListe' => Url::to(['/nexus-reaktionen/liste/index']),
                'urlSetzen' => Url::to(['/nexus-reaktionen/reaktion/setzen']),
                'urlNachrichtenListe' => Url::to(['/nexus-reaktionen/nachricht/liste']),
                'urlNachrichtSetzen' => Url::to(['/nexus-reaktionen/nachricht/setzen']),
                'texte' => self::texte(),
            ];
            Yii::$app->view->registerJs('window.nexusReaktionenKonfig = ' . Json::htmlEncode($konfig) . ';', View::POS_HEAD);
            ReaktionenAsset::register(Yii::$app->view);
        } catch (Throwable $e) {
            Yii::error('nexus-reaktionen (Skript): ' . $e->getMessage(), 'nexus-reaktionen');
        }
    }

    /** Neue Nachricht mit Antwort-Bezug (Feld nexusAntwortAuf aus dem Antwortformular) -> Bezug merken */
    public static function onNachrichtGespeichert($event): void
    {
        try {
            $eintrag = $event->sender;
            $anfrage = Yii::$app->request;
            if (!$eintrag instanceof AbstractMessageEntry || !$anfrage instanceof \yii\web\Request) {
                return;
            }
            $antwortAuf = (int)$anfrage->post('nexusAntwortAuf', 0);
            if ($antwortAuf > 0) {
                Nachrichten::antwortMerken($eintrag, $antwortAuf);
            }
        } catch (Throwable $e) {
            Yii::error('nexus-reaktionen (Antwort-Bezug): ' . $e->getMessage(), 'nexus-reaktionen');
        }
    }

    private static function texte(): array
    {
        $sprache = strtolower(substr((string)Yii::$app->language, 0, 2));
        $alle = [
            'de' => ['knopf' => 'Reagieren', 'sie' => 'Du', 'und' => 'und', 'weitere' => 'weitere', 'fehler' => 'Die Reaktion konnte nicht gespeichert werden.',
                'antworten' => 'Antworten', 'antwortAuf' => 'Antwort auf', 'abbrechen' => 'Antwort abbrechen', 'geloescht' => 'Ursprüngliche Nachricht nicht mehr vorhanden', 'eigene' => 'deine Nachricht'],
            'en' => ['knopf' => 'React', 'sie' => 'You', 'und' => 'and', 'weitere' => 'more', 'fehler' => 'The reaction could not be saved.',
                'antworten' => 'Reply', 'antwortAuf' => 'Reply to', 'abbrechen' => 'Cancel reply', 'geloescht' => 'Original message no longer available', 'eigene' => 'your message'],
            'es' => ['knopf' => 'Reaccionar', 'sie' => 'Tú', 'und' => 'y', 'weitere' => 'más', 'fehler' => 'No se pudo guardar la reacción.',
                'antworten' => 'Responder', 'antwortAuf' => 'Respuesta a', 'abbrechen' => 'Cancelar respuesta', 'geloescht' => 'El mensaje original ya no existe', 'eigene' => 'tu mensaje'],
        ];
        return $alle[$sprache] ?? $alle['en'];
    }
}
