<?php

namespace humhub\modules\nexusVoice;

use humhub\modules\nexusVoice\assets\VoiceAsset;
use Yii;

/**
 * Sprachnachrichten (Josh, 26.09.2026: "in Beitraegen, in Kommentaren, in privaten Nachrichten",
 * max. 5 Minuten, vorerst ohne Mitschrift).
 *
 * Bewusst KEIN eigener Speicher und KEIN Eingriff in HumHubs Formulare: das Skript
 * (resources/js/nexus.voice.js) setzt neben jeden vorhandenen Datei-Upload-Knopf einen
 * Mikrofon-Knopf und reicht die fertige Aufnahme an genau diesen Upload weiter
 * (jQuery-fileupload "add") -- sie wird also wie ein ausgewaehltes Foto hochgeladen,
 * gespeichert, berechtigt und geloescht. Angezeigt wird sie mit einem eigenen
 * <audio>-Abspieler, weil HumHubs Abspieler nur .mp3 kennt; Browser nehmen aber
 * WebM (Chrome/Android/Firefox) bzw. M4A (iPhone/Safari) auf.
 * Nur fuer Angemeldete -- Gaeste sehen ohnehin keine Formulare.
 */
class Events
{
    public static function onLayoutAddonInit($event): void
    {
        if (Yii::$app->user->isGuest) {
            return;
        }
        VoiceAsset::register(Yii::$app->view);
    }
}
