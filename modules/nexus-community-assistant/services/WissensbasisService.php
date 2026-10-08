<?php

namespace nexus\modules\communityAssistant\services;

/**
 * Baut den Wissensdatenbank-Teil des System-Prompts aus fest hinterlegten,
 * massgeblichen N.E.X.U.S.-Dokumenten (knowledge/-Ordner dieses Moduls).
 * Gleiches Prinzip wie beim Telegram-Bot (config/knowledge_base.py) --
 * bewusst dieselben Kern-Dokumente, damit beide Assistenten auf derselben
 * Grundlage antworten. Die Telegram-Bot-eigene Spezifikation wurde bewusst
 * NICHT uebernommen -- die beschreibt Telegram-Befehle, die es in HumHub
 * gar nicht gibt, und wuerde den Assistenten nur verwirren.
 *
 * Ergebnis wird pro Lauf einmal zusammengebaut und im Speicher
 * zwischengespeichert (die Dateien aendern sich nicht waehrend eines
 * einzelnen PHP-Requests).
 */
class WissensbasisService
{
    private const VOLLTEXT_DATEIEN = [
        'NEXUS_Charta.txt',
        'NEXUS_Charta_Grundordnung.txt',
        'NEXUS_Gemeinschafts-Gruendungsleitfaden_V1.txt',
        'NEXUS_OneApp_Implementierungsplan_V5.2.md',
        'BAUPLAN_Inhaltsverzeichnis.txt',
        // Bedienung des Portals (Josh, 07.10.2026: "kennt Nova sich mit dem Portal aus?" -- bis dahin nicht)
        'Portal_Handbuch.md',
    ];

    // Sehr grosse Dokumente nur auszugsweise (erste N Zeilen) mitgeben,
    // damit der Prompt nicht unnoetig aufgeblaeht wird.
    private const AUSZUG_DATEIEN = [
        'NEXUS_OneApp_Bedienungsanleitung_v0.2.0-alpha.md' => 200,
    ];

    private static ?string $cache = null;

    public static function text(): string
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $ordner = dirname(__DIR__) . '/knowledge';
        $teile = [];

        foreach (self::VOLLTEXT_DATEIEN as $datei) {
            $pfad = $ordner . '/' . $datei;
            if (!is_file($pfad)) {
                continue;
            }
            $inhalt = trim(self::alsUtf8Lesen($pfad));
            if ($inhalt !== '') {
                $teile[] = "=== {$datei} ===\n{$inhalt}";
            }
        }

        foreach (self::AUSZUG_DATEIEN as $datei => $maxZeilen) {
            $pfad = $ordner . '/' . $datei;
            if (!is_file($pfad)) {
                continue;
            }
            $zeilen = explode("\n", self::alsUtf8Lesen($pfad));
            $auszug = implode("\n", array_slice($zeilen, 0, $maxZeilen));
            $teile[] = "=== {$datei} (Auszug, erste {$maxZeilen} Zeilen) ===\n{$auszug}\n"
                . '[... gekuerzt ...]';
        }

        $anleitungen = self::anleitungen();
        if ($anleitungen !== '') {
            $teile[] = "=== ANLEITUNGEN IM PORTAL (Kreis Willkommen & Orientierung, aktueller Stand) ===\n"
                . "Bei Bedienungsfragen auf die passende Anleitung verweisen (Titel und Link nennen).\n\n"
                . $anleitungen;
        }

        self::$cache = implode("\n\n", $teile);
        return self::$cache;
    }

    /**
     * Die Anleitungen live aus dem Portal -- so bleibt Nova aktuell, wenn jemand eine Anleitung
     * bearbeitet oder eine neue schreibt. Fehlt das Wiki-Modul, gibt es eben keine.
     */
    private static function anleitungen(): string
    {
        $klasse = 'humhub\modules\wiki\models\WikiPage';
        // Moduleinstellung "anleitungenKreis": dessen Wiki-Seiten sind die Anleitungen fuer Mitglieder.
        $kreisId = (int)\nexus\modules\communityAssistant\Module::instanz()->anleitungenKreis;
        $kreis = $kreisId > 0 ? \humhub\modules\space\models\Space::findOne(['id' => $kreisId]) : null;
        if (!class_exists($klasse) || $kreis === null) {
            return '';
        }
        $basis = rtrim((string)\Yii::$app->settings->get('baseUrl'), '/');
        $teile = [];
        try {
            foreach ($klasse::find()->contentContainer($kreis)->all() as $seite) {
                $text = trim((string)($seite->latestRevision->content ?? ''));
                if ($text === '') {
                    continue;
                }
                $pfad = preg_replace('#^.*?(/s/)#', '$1', (string)$seite->getUrl());
                $teile[] = "--- {$seite->title} ({$basis}{$pfad}) ---\n" . mb_substr($text, 0, 8000);
            }
        } catch (\Throwable $e) {
            \Yii::warning('nexus-community-assistant: Anleitungen nicht lesbar: ' . $e->getMessage());
        }
        return implode("\n\n", $teile);
    }

    /**
     * Liest eine Textdatei robust als UTF-8 ein. Manche der hinterlegten
     * Dokumente (z.B. die Charta-Dateien) stammen aus alten Windows-
     * Textexporten und sind NICHT in UTF-8 kodiert -- ein direkter
     * json_encode() dieser Bytes schlaegt sonst fehl (Gemini-Aufruf bricht
     * dann komplett ab, ohne erkennbaren Grund: "contents is not
     * specified"). Gleiches Prinzip wie beim Telegram-Bot
     * (config/knowledge_base.py, mehrere Kodierungen der Reihe nach probieren).
     */
    private static function alsUtf8Lesen(string $pfad): string
    {
        $roh = (string)file_get_contents($pfad);

        if (mb_check_encoding($roh, 'UTF-8')) {
            return $roh;
        }

        foreach (['Windows-1252', 'ISO-8859-1'] as $kodierung) {
            $konvertiert = @mb_convert_encoding($roh, 'UTF-8', $kodierung);
            if ($konvertiert !== false && mb_check_encoding($konvertiert, 'UTF-8')) {
                return $konvertiert;
            }
        }

        // Letzter Ausweg: ungueltige Bytes entfernen statt die ganze Datei
        // zu verwerfen.
        return mb_convert_encoding($roh, 'UTF-8', 'UTF-8');
    }
}
