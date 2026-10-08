<?php

namespace nexus\modules\communityAssistant\services;

use RuntimeException;
use Yii;

/**
 * Ruft Google Gemini auf -- nutzt bewusst denselben, bereits hinterlegten
 * Schluessel wie das Uebersetzen-Modul (Einstellung "googleApiKey" dort),
 * statt einen zweiten Schluessel/ein zweites Geheimnis zu verlangen. Kein
 * eigener API-Schluessel dieses Moduls.
 */
class AiService
{
    private const STANDARD_MODELL = 'gemini-3.7-flash';

    /**
     * @throws RuntimeException bei fehlendem Schluessel, Verbindungsfehlern
     *   oder einer Fehlerantwort des Dienstes.
     */
    public function frage(string $prompt, ?string $systemAnweisung = null, float $temperature = 0.6): string
    {
        $uebersetzenModul = Yii::$app->getModule('nexus-translate');
        $apiKey = $uebersetzenModul ? $uebersetzenModul->settings->get('googleApiKey') : null;
        if (empty($apiKey)) {
            throw new RuntimeException('Kein Google-API-Schluessel hinterlegt (Uebersetzen-Modul).');
        }

        $modell = ($uebersetzenModul ? $uebersetzenModul->settings->get('modell') : null) ?: self::STANDARD_MODELL;

        $volltext = $systemAnweisung !== null ? ($systemAnweisung . "\n\n" . $prompt) : $prompt;

        $anfrage = [
            'contents' => [[
                'parts' => [['text' => $volltext]],
            ]],
            'generationConfig' => ['temperature' => $temperature],
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modell}:generateContent?key={$apiKey}";

        $anfrageJson = json_encode($anfrage);
        if ($anfrageJson === false) {
            // Kommt vor, wenn z.B. eine der Wissensbasis-Dateien nicht
            // gueltig UTF-8-kodiert ist -- ohne diese Pruefung schickt curl
            // dann einen leeren Anfragekoerper, und Gemini meldet nur ein
            // nichtssagendes "contents is not specified".
            throw new RuntimeException('Anfrage konnte nicht als JSON kodiert werden (' . json_last_error_msg() . ').');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $anfrageJson,
            // Groesszuegiger als vorher (40s) -- seit der Wissensbasis-
            // Anbindung (09.09.2026) ist der System-Prompt deutlich groesser
            // (mehrere zehntausend Zeichen offizielle Dokumente), das braucht
            // etwas mehr Verarbeitungszeit.
            CURLOPT_TIMEOUT => 60,
        ]);
        $antwort = curl_exec($ch);
        $fehler = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($fehler) {
            throw new RuntimeException("Verbindung zu Gemini fehlgeschlagen: {$fehler}");
        }

        $daten = json_decode((string)$antwort, true);

        if ($code !== 200) {
            $meldung = $daten['error']['message'] ?? "HTTP {$code}";
            throw new RuntimeException("Gemini meldet einen Fehler: {$meldung}");
        }

        $ergebnis = $daten['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ($ergebnis === null) {
            throw new RuntimeException('Gemini hat keinen Text zurueckgegeben.');
        }

        return trim($ergebnis);
    }
}
