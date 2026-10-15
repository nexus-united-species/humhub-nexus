<?php

namespace nexus\modules\projekte\services;

use nexus\modules\projekte\models\Karte;
use nexus\modules\projekte\models\Stimme;
use nexus\modules\projekte\Module;

/**
 * Wie ueber Vorschlaege entschieden wird -- je Projekt einstellbar.
 *
 * - einfach:  "Passt so" / "Ich habe eine Aenderung" (wie bisher bei allen Kacheln)
 * - konsent:  Zustimmung / Bedenken / schwerwiegender Einwand / Enthaltung. Angenommen, wenn am Ende
 *             niemand einen schwerwiegenden Einwand hat (und mindestens eine Zustimmung da ist).
 *             Bedenken halten nichts auf, werden aber festgehalten -- so wie im Konsent-Verfahren.
 * - mehrheit: Ja / Nein / Enthaltung. Angenommen bei mehr Ja als Nein.
 */
class Entscheidung
{
    public const REGELN = ['einfach', 'konsent', 'mehrheit'];

    /** Antwortmoeglichkeiten: art => [Text, Symbol, Farbklasse, Begruendung Pflicht] */
    private const OPTIONEN = [
        'einfach' => [
            'ok' => ['passt', 'thumbs-o-up', 'nx-pj-gruen', false],
            'aenderung' => ['aenderung', 'pencil', 'nx-pj-gelb', true],
        ],
        'konsent' => [
            'zustimmung' => ['opt_zustimmung', 'check', 'nx-pj-gruen', false],
            'bedenken' => ['opt_bedenken', 'exclamation-circle', 'nx-pj-gelb', true],
            'einwand' => ['opt_einwand', 'hand-paper-o', 'nx-pj-rot', true],
            'enthaltung' => ['opt_enthaltung', 'circle-o', 'nx-pj-leise', false],
        ],
        'mehrheit' => [
            'ja' => ['opt_ja', 'thumbs-o-up', 'nx-pj-gruen', false],
            'nein' => ['opt_nein', 'thumbs-o-down', 'nx-pj-rot', false],
            'enthaltung' => ['opt_enthaltung', 'circle-o', 'nx-pj-leise', false],
        ],
    ];

    public static function regel(?string $regel): string
    {
        return in_array($regel, self::REGELN, true) ? $regel : 'einfach';
    }

    /** Regel fuer diese Kachel: Vorschlaege folgen dem Projekt, normale Aufgaben bleiben einfach. */
    public static function regelFuer(Karte $karte): string
    {
        return $karte->istVorschlag() ? self::regel($karte->projekt->regel ?? null) : 'einfach';
    }

    /** So viele Tage laeuft eine Runde, wenn kein Datum gewaehlt wird */
    public static function rundenTage(Karte $karte): int
    {
        if ($karte->istVorschlag() && (int)$karte->projekt->frist_tage > 0) {
            return (int)$karte->projekt->frist_tage;
        }
        return (int)(Module::instanz()?->abstimmungTage ?? 7);
    }

    /** Zustimmende Antwort (fuer die gruene Zahl auf der Tafel) */
    public static function istPositiv(string $art): bool
    {
        return in_array($art, ['ok', 'zustimmung', 'ja'], true);
    }

    /** @return array<string, array{0: string, 1: string, 2: string, 3: bool}> */
    public static function optionen(string $regel): array
    {
        return self::OPTIONEN[self::regel($regel)];
    }

    public static function istGueltig(string $regel, string $art): bool
    {
        return isset(self::OPTIONEN[self::regel($regel)][$art]);
    }

    public static function brauchtBegruendung(string $regel, string $art): bool
    {
        return (bool)(self::OPTIONEN[self::regel($regel)][$art][3] ?? false);
    }

    /** @return array<string, int> art => Anzahl in der laufenden Runde */
    public static function zahlen(Karte $karte): array
    {
        $roh = Stimme::find()->select(['n' => 'COUNT(*)', 'art'])->where(['karte_id' => $karte->id, 'runde' => $karte->runde])
            ->groupBy('art')->indexBy('art')->column();
        $zahlen = [];
        foreach (array_keys(self::optionen(self::regelFuer($karte))) as $art) {
            $zahlen[$art] = (int)($roh[$art] ?? 0);
        }
        return $zahlen;
    }

    /**
     * Ergebnis, wenn die Runde jetzt endet.
     * @return array{ergebnis: string, text: string}  ergebnis: angenommen | abgelehnt
     */
    public static function auswertung(Karte $karte): array
    {
        $regel = self::regelFuer($karte);
        $z = self::zahlen($karte);
        $teile = [];
        foreach (self::optionen($regel) as $art => $o) {
            $teile[] = $z[$art] . ' × ' . Texte::t($o[0]);
        }
        $text = implode(', ', $teile);
        $angenommen = match ($regel) {
            'konsent' => $z['einwand'] === 0 && $z['zustimmung'] > 0,
            'mehrheit' => $z['ja'] > $z['nein'],
            default => $z['aenderung'] === 0 && $z['ok'] > 0,
        };
        return ['ergebnis' => $angenommen ? 'angenommen' : 'abgelehnt', 'text' => $text];
    }
}
