<?php

namespace nexus\modules\gesundheit\services;

use nexus\modules\gesundheit\models\Artikel;
use Yii;

/**
 * Suche fuer Menschen mit einer Beschwerde: "Bauchweh" soll "Reizdarm" finden.
 *
 * Gewichtung je Suchwort: Kurztitel exakt 30, im Titel 12, Schlagwort 8, Suchwort (Alltagswort,
 * Synonym) 8, im Text 2. Woerter werden mit Umlauten ausgeschrieben verglichen ("uebelkeit" =
 * "Uebelkeit" = "Übelkeit"), Teilwoerter zaehlen ("Kopfschmerz" findet "Kopfschmerzen").
 * 525 Artikel passen bequem in den Speicher -- deshalb ohne eigenen Suchindex.
 */
class Suche
{
    public const MAX_TREFFER = 40;
    private const MIN_WORT = 3;
    private const FUELLWOERTER = ['und', 'oder', 'bei', 'mit', 'der', 'die', 'das', 'ein', 'eine', 'von', 'fuer', 'gegen', 'was', 'hilft', 'wie', 'ich', 'habe', 'mein', 'meine', 'mir'];

    /** @return array<int, array{artikel: array, punkte: int}> */
    public static function suche(string $frage): array
    {
        $woerter = array_values(array_filter(
            array_unique(explode(' ', Slug::normal($frage))),
            fn($w) => mb_strlen($w) >= self::MIN_WORT && !in_array($w, self::FUELLWOERTER, true)
        ));
        if ($woerter === []) {
            return [];
        }
        $bestand = self::bestand();
        $punkte = [];
        foreach ($bestand['artikel'] as $id => $a) {
            $p = 0;
            foreach ($woerter as $w) {
                if ($a['kurz_n'] === $w) {
                    $p += 30;
                } elseif (str_contains($a['titel_n'], $w)) {
                    $p += 12;
                }
                foreach ($bestand['worte'][$id] ?? [] as [$wort, $art]) {
                    if (str_contains($wort, $w) || ($w !== '' && str_contains($w, $wort) && mb_strlen($wort) >= 5)) {
                        $p += 8;
                        break;
                    }
                }
            }
            if ($p > 0) {
                $punkte[$id] = $p;
            }
        }
        // Volltext: Datenbank vergleicht ohne Akzente (utf8mb4_unicode_ci), also auch "Ubelkeit"
        foreach ($woerter as $w) {
            $roh = self::rohwort($frage, $w);
            foreach (Artikel::find()->select('id')->where(['like', 'inhalt', $roh])->column() as $id) {
                $punkte[(int)$id] = ($punkte[(int)$id] ?? 0) + 2;
            }
        }
        arsort($punkte);
        $ergebnis = [];
        foreach (array_slice($punkte, 0, self::MAX_TREFFER, true) as $id => $p) {
            $ergebnis[] = ['artikel' => $bestand['artikel'][$id], 'punkte' => $p];
        }
        return $ergebnis;
    }

    /** Das Suchwort so, wie es im Text stehen koennte (mit Umlaut, falls so eingegeben). */
    private static function rohwort(string $frage, string $normal): string
    {
        foreach (preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($frage)) as $teil) {
            if (Slug::normal($teil) === $normal) {
                return $teil;
            }
        }
        return $normal;
    }

    /** @return array{artikel: array<int, array>, worte: array<int, array<int, array{0: string, 1: string}>>} */
    public static function bestand(): array
    {
        return Yii::$app->cache->getOrSet(['nexus-gesundheit-bestand', Darstellung::stand(), self::wortStand()], function () {
            $artikel = [];
            foreach (Artikel::find()->select(['id', 'slug', 'titel', 'kurztitel', 'thema', 'inhalt'])->orderBy('titel')->asArray()->all() as $z) {
                $artikel[(int)$z['id']] = [
                    'id' => (int)$z['id'],
                    'slug' => $z['slug'],
                    'titel' => $z['titel'],
                    'thema' => $z['thema'],
                    'anriss' => Darstellung::anriss((string)$z['inhalt']),
                    'titel_n' => Slug::normal((string)$z['titel']),
                    'kurz_n' => Slug::normal((string)$z['kurztitel']),
                ];
            }
            $worte = [];
            foreach ((new \yii\db\Query())->select(['artikel_id', 'wort', 'art'])->from('nexus_gesundheit_wort')->all() as $z) {
                $worte[(int)$z['artikel_id']][] = [Slug::normal((string)$z['wort']), (string)$z['art']];
            }
            return ['artikel' => $artikel, 'worte' => $worte];
        }, 86400);
    }

    public static function wortStand(): string
    {
        return (string)(new \yii\db\Query())->from('nexus_gesundheit_wort')->max('id') . '|'
            . (new \yii\db\Query())->from('nexus_gesundheit_wort')->count();
    }

    /** @return array<string, int> Schlagwort => Anzahl Artikel (die haeufigsten) */
    public static function haeufigeSchlagworte(int $anzahl = 40): array
    {
        return Yii::$app->cache->getOrSet(['nexus-gesundheit-haeufig', self::wortStand(), $anzahl], function () use ($anzahl) {
            return array_map('intval', (new \yii\db\Query())->select(['n' => 'COUNT(*)', 'wort'])->from('nexus_gesundheit_wort')
                ->where(['art' => 'schlagwort'])->groupBy('wort')->having(['>', 'COUNT(*)', 1])
                ->orderBy(['n' => SORT_DESC, 'wort' => SORT_ASC])->limit($anzahl)->indexBy('wort')->column());
        }, 3600);
    }

    /** @return int[] Artikel-IDs mit diesem Schlagwort */
    public static function mitSchlagwort(string $wort): array
    {
        return array_map('intval', (new \yii\db\Query())->select('artikel_id')->from('nexus_gesundheit_wort')
            ->where(['art' => 'schlagwort', 'wort' => $wort])->column());
    }

    /**
     * "Siehe auch": Artikel mit den meisten gemeinsamen Schlagworten.
     * @return array<int, array> hoechstens $anzahl Artikel aus bestand()
     */
    public static function verwandt(int $artikelId, int $anzahl = 5): array
    {
        $bestand = self::bestand();
        $eigene = [];
        foreach ($bestand['worte'][$artikelId] ?? [] as [$wort, $art]) {
            if ($art === 'schlagwort') {
                $eigene[$wort] = true;
            }
        }
        if ($eigene === []) {
            return [];
        }
        $gemeinsam = [];
        foreach ($bestand['worte'] as $id => $liste) {
            if ($id === $artikelId) {
                continue;
            }
            foreach ($liste as [$wort, $art]) {
                if ($art === 'schlagwort' && isset($eigene[$wort])) {
                    $gemeinsam[$id] = ($gemeinsam[$id] ?? 0) + 1;
                }
            }
        }
        arsort($gemeinsam);
        $ergebnis = [];
        foreach (array_slice($gemeinsam, 0, $anzahl, true) as $id => $n) {
            if ($n >= 2 && isset($bestand['artikel'][$id])) {
                $ergebnis[] = $bestand['artikel'][$id];
            }
        }
        return $ergebnis;
    }
}
