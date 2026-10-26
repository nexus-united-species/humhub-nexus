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

    /**
     * Laengere Markdown-Texte werden in Stuecke dieser Groesse (Zeichen) zerlegt und gleichzeitig uebersetzt.
     * Eine Wiki-Seite mit 13.000 Zeichen ueberschritt am 26.10.2026 in EINER Anfrage die 60 Sekunden
     * (Gemini lieferte nichts, "Operation timed out"); ein Stueck von 3.500 Zeichen braucht 7-25 s.
     * Gemessen: 5 Stuecke a ~3.300 Zeichen in zwei Runden zu je 4 liefen 46-53 s -- kleinere Stuecke, alle
     * auf einmal, sind schneller (die Dauer haengt an der Laenge der Antwort).
     */
    private const TEILGROESSE = 2000;
    /** So viele Stuecke laufen gleichzeitig. */
    private const GLEICHZEITIG = 8;

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
     * Lange Markdown-Texte werden in Absatz-Stuecken gleichzeitig uebersetzt (siehe TEILGROESSE).
     *
     * @throws RuntimeException bei fehlendem Schluessel, Verbindungsfehlern
     *   oder einer Fehlerantwort des Dienstes. Wird vom aufrufenden
     *   Controller abgefangen und als verstaendliche Meldung
     *   weitergereicht.
     */
    public function uebersetzen(string $text, string $zielsprache, string $zusatz = '', bool $markdown = false): string
    {
        if ($markdown && mb_strlen($text) > self::TEILGROESSE) {
            $teile = self::inTeile($text, self::TEILGROESSE);
            if (count($teile) > 1) {
                return implode("\n\n", $this->teileUebersetzen($teile, $zielsprache, $zusatz));
            }
        }
        return $this->einzeln($text, $zielsprache, $zusatz, $markdown);
    }

    /**
     * Zerlegt Markdown an Absatzgrenzen (Leerzeilen) in Stuecke von hoechstens $max Zeichen -- nie mitten in
     * einem Codeblock, und eine Ueberschrift bleibt bei dem Absatz, zu dem sie gehoert. Ein einzelner Absatz,
     * der laenger ist als $max, bleibt ein eigenes (grosses) Stueck.
     *
     * @return string[]
     */
    public static function inTeile(string $text, int $max): array
    {
        // Absaetze: Leerzeilen ausserhalb von ``` ... ``` trennen
        $absaetze = [];
        $aktuell = [];
        $imCode = false;
        foreach (preg_split('/\R/', $text) as $zeile) {
            if (preg_match('/^\s*```/', $zeile)) {
                $imCode = !$imCode;
            }
            if (!$imCode && trim($zeile) === '' && !preg_match('/^\s*```/', $zeile)) {
                if ($aktuell !== []) {
                    $absaetze[] = implode("\n", $aktuell);
                    $aktuell = [];
                }
                continue;
            }
            $aktuell[] = $zeile;
        }
        if ($aktuell !== []) {
            $absaetze[] = implode("\n", $aktuell);
        }

        $teile = [];
        $puffer = [];
        $laenge = 0;
        foreach ($absaetze as $absatz) {
            $l = mb_strlen($absatz) + 2;
            if ($puffer !== [] && $laenge + $l > $max) {
                // Eine Ueberschrift am Ende des Stuecks gehoert zum naechsten Absatz
                $ende = [];
                while ($puffer !== [] && preg_match('/^#{1,6}\s/', end($puffer))) {
                    array_unshift($ende, array_pop($puffer));
                }
                if ($puffer !== []) {
                    $teile[] = implode("\n\n", $puffer);
                    $puffer = $ende;
                } else {
                    $puffer = $ende; // nur Ueberschriften -- zusammenbleiben
                }
                $laenge = array_sum(array_map(fn($p) => mb_strlen($p) + 2, $puffer));
            }
            $puffer[] = $absatz;
            $laenge += $l;
        }
        if ($puffer !== []) {
            $teile[] = implode("\n\n", $puffer);
        }
        return $teile;
    }

    /**
     * Uebersetzt die Stuecke in Gruppen gleichzeitig (curl_multi). Scheitert eines (Zeitueberschreitung,
     * Ueberlastung), wird NUR dieses Stueck einmal einzeln wiederholt -- dann bricht nicht alles ab.
     *
     * @param string[] $teile
     * @return string[] in derselben Reihenfolge
     */
    private function teileUebersetzen(array $teile, string $zielsprache, string $zusatz): array
    {
        $ergebnis = [];
        foreach (array_chunk($teile, self::GLEICHZEITIG, true) as $gruppe) {
            $multi = curl_multi_init();
            $handles = [];
            foreach ($gruppe as $nr => $teil) {
                [$url, $koerper] = $this->vorbereiten($teil, $zielsprache, $zusatz, true);
                $ch = $this->curlHandle($url, $koerper, 60);
                curl_multi_add_handle($multi, $ch);
                $handles[$nr] = $ch;
            }
            do {
                $status = curl_multi_exec($multi, $laeuft);
                if ($laeuft) {
                    curl_multi_select($multi, 1.0);
                }
            } while ($laeuft && $status === CURLM_OK);

            foreach ($handles as $nr => $ch) {
                $antwort = curl_multi_getcontent($ch);
                $fehler = curl_error($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_multi_remove_handle($multi, $ch);
                curl_close($ch);
                try {
                    $ergebnis[$nr] = $this->auswerten((string)$antwort, $code, $fehler);
                } catch (RuntimeException $e) {
                    Yii::warning('Teil ' . ($nr + 1) . '/' . count($teile) . ' fehlgeschlagen, wiederhole einzeln: ' . $e->getMessage(), 'nexus-translate');
                    $ergebnis[$nr] = $this->einzeln($gruppe[$nr], $zielsprache, $zusatz, true);
                }
            }
            curl_multi_close($multi);
        }
        ksort($ergebnis);
        return array_values($ergebnis);
    }

    /** @return array{0: string, 1: string} URL und JSON-Koerper */
    private function vorbereiten(string $text, string $zielsprache, string $zusatz, bool $markdown): array
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

        return ["https://generativelanguage.googleapis.com/v1beta/models/{$modell}:generateContent?key={$apiKey}", json_encode($anfrage)];
    }

    private function curlHandle(string $url, string $koerper, int $zeitlimit)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $koerper,
            CURLOPT_TIMEOUT => $zeitlimit,
        ]);
        return $ch;
    }

    /** Liest die Antwort des Dienstes aus; wirft bei Verbindungsfehler, Fehlercode oder leerer Antwort. */
    private function auswerten(string $antwort, int $code, string $fehler): string
    {
        if ($fehler) {
            throw new RuntimeException("Verbindung zum Uebersetzungsdienst fehlgeschlagen: {$fehler}");
        }

        $daten = json_decode($antwort, true);

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

    /** Eine einzelne Anfrage (mit EINER Wiederholung bei Ueberlastung). */
    private function einzeln(string $text, string $zielsprache, string $zusatz, bool $markdown): string
    {
        [$url, $koerper] = $this->vorbereiten($text, $zielsprache, $zusatz, $markdown);

        // Automatisch (Markdown) bis 60 s: der Willkommens-Beitrag (11.000 Zeichen) braucht gemessen
        // ~21 s und riss am 25.09.2026 bei 25 s ab -- besonders, wenn beim Oeffnen eines Kreises
        // mehrere Uebersetzungen gleichzeitig laufen. Ist der Dienst kurz ueberlastet (429/5xx),
        // wird EINMAL nach 2 s wiederholt; eine Zeitueberschreitung nicht (dauerte sonst doppelt).
        $versuche = 0;
        do {
            $ch = $this->curlHandle($url, $koerper, $markdown ? 60 : 25);
            $antwort = curl_exec($ch);
            $fehler = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $nochmal = !$fehler && ($code === 429 || $code >= 500) && ++$versuche < 2;
            if ($nochmal) {
                sleep(2);
            }
        } while ($nochmal);

        return $this->auswerten((string)$antwort, $code, $fehler);
    }
}
