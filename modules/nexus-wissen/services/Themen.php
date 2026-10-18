<?php

namespace nexus\modules\wissen\services;

use nexus\modules\wissen\models\Artikel;
use nexus\modules\wissen\models\Thema;

/**
 * Die Kategorien (Themen) einer Sammlung -- in der Datenbank, damit die Redaktion sie im Portal anlegen,
 * umbenennen, verschieben und loeschen kann.
 *
 * Beim ersten Aufruf je Sammlung werden sie aus den Einstellungen ("themen" in common.php) und den
 * Themen der vorhandenen Artikel uebernommen -- in genau der Reihenfolge, die es vorher gab. Danach
 * gilt nur noch die Datenbank. Bringt ein spaeterer Import ein neues Thema mit, kommt es hinten dazu.
 */
class Themen
{
    public const VORGABE_SYMBOL = 'book';

    /** Zur Auswahl beim Anlegen -- Font-Awesome-4-Namen, wie sie das Portal kennt. */
    public const SYMBOLE = [
        'book', 'leaf', 'heartbeat', 'medkit', 'stethoscope', 'cutlery', 'bed', 'shield', 'compass', 'globe',
        'home', 'users', 'child', 'graduation-cap', 'lightbulb-o', 'cogs', 'wrench', 'laptop', 'mobile',
        'microchip', 'wifi', 'signal', 'lock', 'key', 'comments', 'question-circle', 'tree', 'sun-o',
        'tint', 'bolt', 'recycle', 'money', 'balance-scale', 'music', 'paint-brush', 'camera', 'map-marker',
    ];

    /** @return Thema[] in der eingestellten Reihenfolge */
    public static function alle(Sammlung $sammlung, array $einstellungen): array
    {
        $vorhanden = Thema::find()->where(['sammlung' => $sammlung->kennung])->orderBy(['sortierung' => SORT_ASC, 'id' => SORT_ASC])->all();
        if ($vorhanden === []) {
            self::ausEinstellungen($sammlung->kennung, $einstellungen);
            $vorhanden = Thema::find()->where(['sammlung' => $sammlung->kennung])->orderBy(['sortierung' => SORT_ASC, 'id' => SORT_ASC])->all();
        }
        // Themen von Artikeln, die (noch) keine Kategorie haben -- etwa nach einem Import
        $codes = array_flip(array_map(fn(Thema $t) => $t->code, $vorhanden));
        $fehlend = array_values(array_filter(
            array_map('strval', Artikel::inSammlung($sammlung->kennung)->select('thema')->distinct()->column()),
            fn($c) => $c !== '' && !isset($codes[$c])
        ));
        if ($fehlend !== []) {
            sort($fehlend, SORT_NATURAL);
            $platz = $vorhanden === [] ? 0 : max(array_map(fn(Thema $t) => (int)$t->sortierung, $vorhanden));
            foreach ($fehlend as $code) {
                $vorhanden[] = self::anlegen($sammlung->kennung, $code, Sammlung::nameAusCode($code), self::VORGABE_SYMBOL, $platz += 10);
            }
        }
        return $vorhanden;
    }

    private static function ausEinstellungen(string $kennung, array $einstellungen): void
    {
        $platz = 0;
        foreach ((array)($einstellungen['themen'] ?? []) as $code => $wert) {
            $code = (string)$code;
            $namen = is_array($wert) ? array_diff_key($wert, ['symbol' => 1]) : (string)$wert;
            $symbol = is_array($wert) && preg_match('/^[a-z0-9-]+$/', (string)($wert['symbol'] ?? '')) ? $wert['symbol'] : self::VORGABE_SYMBOL;
            if ($namen === '' || $namen === []) {
                $namen = Sammlung::nameAusCode($code);
            }
            self::anlegen($kennung, $code, $namen, $symbol, $platz += 10);
        }
    }

    /** @param string|array<string, string> $name */
    public static function anlegen(string $kennung, string $code, $name, string $symbol, int $sortierung): Thema
    {
        $t = new Thema([
            'sammlung' => $kennung,
            'code' => mb_substr($code, 0, 100),
            'name' => is_array($name) ? json_encode($name, JSON_UNESCAPED_UNICODE) : $name,
            'symbol' => in_array($symbol, self::SYMBOLE, true) || preg_match('/^[a-z0-9-]+$/', $symbol) ? $symbol : self::VORGABE_SYMBOL,
            'sortierung' => $sortierung,
        ]);
        $t->save(false);
        return $t;
    }

    /** Freier Code fuer eine neue Kategorie: aus dem Namen gebildet, nie ein schon vergebener. */
    public static function freierCode(string $kennung, string $name): string
    {
        $basis = Slug::aus($name, 90) ?: 'thema';
        $code = $basis;
        for ($i = 2; Thema::find()->where(['sammlung' => $kennung, 'code' => $code])->exists(); $i++) {
            $code = $basis . '-' . $i;
        }
        return $code;
    }

    /** Eine Kategorie eine Stelle nach oben (-1) oder unten (+1) schieben. */
    public static function verschieben(Sammlung $sammlung, array $einstellungen, int $id, int $richtung): void
    {
        $liste = array_values(self::alle($sammlung, $einstellungen));
        foreach ($liste as $i => $t) {
            if ((int)$t->id !== $id) {
                continue;
            }
            $j = $i + ($richtung < 0 ? -1 : 1);
            if (!isset($liste[$j])) {
                return;
            }
            [$liste[$i], $liste[$j]] = [$liste[$j], $liste[$i]];
            break;
        }
        foreach ($liste as $i => $t) {
            if ((int)$t->sortierung !== ($i + 1) * 10) {
                $t->sortierung = ($i + 1) * 10;
                $t->save(false, ['sortierung']);
            }
        }
    }
}
