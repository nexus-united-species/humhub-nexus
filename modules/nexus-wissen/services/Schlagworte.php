<?php

namespace nexus\modules\wissen\services;

use nexus\modules\wissen\models\Artikel;
use Throwable;
use Yii;

/**
 * Schlagworte und Suchwoerter von der KI -- fuer die Konsole (alle Artikel einer Sammlung) und fuer
 * neue Artikel aus dem Portal. Die KI veraendert nie den Artikeltext.
 * Braucht nexus-community-assistant (mit nexus-translate); ohne sie liefert verfuegbar() false.
 */
class Schlagworte
{
    private const KI_DIENST = 'nexus\modules\communityAssistant\services\AiService';
    private const KI_TEXT_ZEICHEN = 6000;
    public const MAX_SCHLAGWORTE = 8;
    public const MAX_SUCHWOERTER = 15;

    private const ANWEISUNG = <<<'TXT'
Du verschlagwortest Artikel eines Nachschlagewerks, damit Menschen den passenden Artikel finden --
auch wenn sie andere Woerter benutzen als der Artikel. Das Nachschlagewerk: {beschreibung}

Antworte NUR mit JSON in genau dieser Form:
{"schlagworte": ["...", "..."], "suchwoerter": ["...", "..."]}

schlagworte: 4 bis 8 kurze, allgemein verstaendliche Begriffe (1-3 Woerter) in der Sprache des
Artikels: die wichtigsten Themen, Gegenstaende und Zielgruppen des Artikels. Verwende moeglichst
gaengige, wiederkehrende Begriffe statt sehr spezieller, damit viele Artikel dieselben teilen.

suchwoerter: 5 bis 15 Woerter, mit denen Laien nach diesem Thema suchen wuerden und die NICHT schon
im Titel stehen: Alltagswoerter, andere Bezeichnungen und Synonyme, Fachbegriffe und ihre
gebraeuchlichen Entsprechungen, typische Fragen oder Probleme, bei denen der Artikel hilft.

Erfinde nichts. Nur Begriffe, die zum Inhalt des Artikels passen.
TXT;

    public static function verfuegbar(): bool
    {
        return class_exists(self::KI_DIENST);
    }

    /**
     * @param int $versuche bei Fehlern erneut fragen (Konsole: 3; im Portal 1, damit niemand wartet)
     * @return array{schlagworte: string[], suchwoerter: string[]}|null null: KI fehlt oder antwortete nicht brauchbar
     */
    public static function erzeugen(Sammlung $sammlung, Artikel $artikel, int $versuche = 1, ?callable $meldung = null): ?array
    {
        if (!self::verfuegbar()) {
            return null;
        }
        $dienst = self::KI_DIENST;
        $ki = new $dienst();
        $anweisung = $sammlung->kiAnweisung() ?: str_replace('{beschreibung}', $sammlung->kiBeschreibung() ?: $sammlung->name('de'), self::ANWEISUNG);
        $text = "Titel: {$artikel->titel}\n\n" . mb_substr($artikel->inhalt, 0, self::KI_TEXT_ZEICHEN);
        for ($versuch = 1; $versuch <= $versuche; $versuch++) {
            try {
                $antwort = $ki->frage($text, $anweisung, 0.2);
                if (preg_match('/\{.*\}/s', (string)$antwort, $m)) {
                    $daten = json_decode($m[0], true);
                    if (is_array($daten) && !empty($daten['schlagworte']) && is_array($daten['schlagworte'])) {
                        return [
                            'schlagworte' => array_slice(array_map('strval', $daten['schlagworte']), 0, self::MAX_SCHLAGWORTE),
                            'suchwoerter' => array_slice(array_map('strval', (array)($daten['suchwoerter'] ?? [])), 0, self::MAX_SUCHWOERTER),
                        ];
                    }
                }
            } catch (Throwable $e) {
                Yii::warning('nexus-wissen: Schlagworte fuer "' . $artikel->titel . '" fehlgeschlagen: ' . $e->getMessage(), 'nexus-wissen');
                if ($meldung !== null) {
                    $meldung("  Versuch $versuch fehlgeschlagen: " . mb_substr($e->getMessage(), 0, 120));
                }
                if ($versuch < $versuche) {
                    sleep(3 * $versuch);
                }
            }
        }
        return null;
    }
}
