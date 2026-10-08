<?php

namespace humhub\modules\nexusTranslate;

use RuntimeException;
use Yii;

class Module extends \humhub\components\Module
{
    // Menschlich lesbare Namen fuer die Sprachauswahl bei Gemini --
    // HumHub liefert nur den Kurzcode (de, en, es, ...).
    private const SPRACHNAMEN = [
        'de' => 'German', 'en' => 'English', 'es' => 'Spanish',
        'fr' => 'French', 'it' => 'Italian', 'pt' => 'Portuguese',
        'nl' => 'Dutch', 'pl' => 'Polish', 'ru' => 'Russian',
        'tr' => 'Turkish', 'ar' => 'Arabic', 'zh-CN' => 'Chinese',
    ];

    public function getName()
    {
        return 'N.E.X.U.S. Uebersetzen';
    }

    public function getDescription()
    {
        return 'Uebersetzen-Knopf an Beitraegen, ueber Google Gemini.';
    }

    /**
     * Uebersetzt einen Text mit Google Gemini.
     *
     * Bewusst per curl statt einer Programmbibliothek -- eine einzelne,
     * seltene Anfrage rechtfertigt keine zusaetzliche Abhaengigkeit.
     *
     * @throws RuntimeException bei fehlendem Schluessel, Verbindungsfehlern
     *   oder einer Fehlerantwort des Dienstes. Wird vom aufrufenden
     *   Controller abgefangen und als verstaendliche Meldung
     *   weitergereicht.
     */
    public function uebersetzen(string $text, string $zielsprache, string $zusatz = '', bool $markdown = false): string
    {
        // Automatische Uebersetzung (Beitraege/Kommentare im Stream) behaelt die
        // Formatierung: Fettschrift, Listen, Links und vor allem Erwaehnungen
        // ([Name](mention:guid ...)) muessen intakt bleiben, sonst zeigt der Beitrag
        // kaputte Links. Der "Translate"-Knopf liefert weiter reinen Text.
        $format = $markdown
            ? 'The text is Markdown. Keep ALL Markdown formatting, line breaks, emojis and URLs exactly as they are. '
              . 'Keep every link and mention of the form [text](target) with its (target) part completely unchanged; '
              . 'for mentions like [Name](mention:...) keep the whole thing unchanged. Output ONLY the translated Markdown -- '
              . 'no explanation, no prefix, no code fences.'
            : 'Output ONLY the translation itself -- no quotes, no explanation, no prefix, no markdown formatting.';

        $apiKey = $this->settings->get('googleApiKey');
        if (empty($apiKey)) {
            throw new RuntimeException('Kein API-Schluessel fuer die Uebersetzung hinterlegt.');
        }

        $modell = $this->settings->get('modell') ?: 'gemini-3.7-flash';
        $zielname = self::SPRACHNAMEN[$zielsprache] ?? $zielsprache;

        $anfrage = [
            'contents' => [[
                'parts' => [[
                    // Glossar IMMER (seit 26.09.2026) -- "Translate"-Knopf liefert z. B. "en-US", daher Kurzcode.
                    'text' => "Translate the following text to {$zielname}. " . $format
                        . ' ' . Glossar::anweisung(strtolower(explode('-', $zielsprache)[0]))
                        . ($zusatz !== '' ? ' ' . $zusatz : '') . "\n\n"
                        . $text,
                ]],
            ]],
            // Niedrige Temperatur: Uebersetzung soll woertlich bleiben,
            // nicht kreativ variieren.
            'generationConfig' => ['temperature' => 0.2],
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modell}:generateContent?key={$apiKey}";

        // Automatisch (Markdown) bis 60 s: der Willkommens-Beitrag (11.000 Zeichen) braucht gemessen
        // ~21 s und riss am 25.09.2026 bei 25 s ab -- besonders, wenn beim Oeffnen eines Kreises
        // mehrere Uebersetzungen gleichzeitig laufen. Ist der Dienst kurz ueberlastet (429/5xx),
        // wird EINMAL nach 2 s wiederholt; eine Zeitueberschreitung nicht (dauerte sonst doppelt).
        $versuche = 0;
        do {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode($anfrage),
                CURLOPT_TIMEOUT => $markdown ? 60 : 25,
            ]);
            $antwort = curl_exec($ch);
            $fehler = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $nochmal = !$fehler && ($code === 429 || $code >= 500) && ++$versuche < 2;
            if ($nochmal) {
                sleep(2);
            }
        } while ($nochmal);

        if ($fehler) {
            throw new RuntimeException("Verbindung zum Uebersetzungsdienst fehlgeschlagen: {$fehler}");
        }

        $daten = json_decode((string)$antwort, true);

        if ($code !== 200) {
            $meldung = $daten['error']['message'] ?? "HTTP {$code}";
            throw new RuntimeException("Uebersetzungsdienst meldet einen Fehler: {$meldung}");
        }

        $ergebnis = $daten['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ($ergebnis === null) {
            throw new RuntimeException('Der Uebersetzungsdienst hat keinen Text zurueckgegeben.');
        }

        return trim($ergebnis);
    }
}
