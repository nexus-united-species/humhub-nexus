<?php

namespace nexus\modules\wissen\services;

use nexus\modules\wissen\models\Artikel;
use Yii;

/**
 * Suche mit Alltagswoertern: "Bauchweh" soll den Artikel ueber Bauchschmerzen finden -- ueber die
 * Suchwoerter, die jeder Artikel (von der Redaktion oder der KI) bekommt.
 *
 * Gewichtung je Suchwort: Kurztitel exakt 30, im Titel 12, Schlagwort 8, Suchwort (Alltagswort,
 * Synonym) 8, im Text 2. Woerter werden mit Umlauten ausgeschrieben verglichen ("uebelkeit" =
 * "Uebelkeit" = "Übelkeit"), Teilwoerter zaehlen ("Kopfschmerz" findet "Kopfschmerzen").
 * Mehrere Woerter: Artikel, die alle treffen, stehen vorn. Woerter werden auf ihren Stamm gekuerzt.
 * Einige tausend Artikel je Sammlung passen bequem in den Speicher -- deshalb ohne eigenen Suchindex.
 */
class Suche
{
    public const MAX_TREFFER = 40;
    private const MIN_WORT = 3;
    private const FUELLWOERTER = ['und', 'oder', 'bei', 'beim', 'mit', 'der', 'die', 'das', 'den', 'dem', 'des', 'ein', 'eine',
        'einen', 'einem', 'von', 'vom', 'fuer', 'gegen', 'was', 'hilft', 'helfen', 'wie', 'ich', 'habe', 'hab', 'hat', 'haben',
        'mein', 'meine', 'meinem', 'meinen', 'mir', 'mich', 'nicht', 'kein', 'keine', 'kann', 'nur', 'sehr', 'mehr', 'auch',
        'wenn', 'immer', 'oft', 'ist', 'sind', 'bin', 'zum', 'zur', 'aus', 'nach', 'ohne', 'viel', 'ganz', 'tut', 'tun',
        'wird', 'werden', 'gut', 'man', 'sich', 'mal', 'schon', 'noch', 'dass', 'damit', 'welche', 'welcher', 'gibt'];
    /** Endungen, die fuer den Vergleich wegfallen ("einschlafen" findet "Einschlafprobleme"). */
    private const ENDUNGEN = ['ungen', 'en', 'er', 'es', 'e', 'n', 's'];
    private const MIN_STAMM = 5;

    /** @return array<int, array{artikel: array, punkte: int}> */
    public static function suche(Sammlung $sammlung, string $frage): array
    {
        $woerter = array_values(array_filter(
            array_unique(explode(' ', Slug::normal($frage))),
            fn($w) => mb_strlen($w) >= self::MIN_WORT && !in_array($w, self::FUELLWOERTER, true)
        ));
        if ($woerter === []) {
            return [];
        }
        $bestand = self::bestand($sammlung->kennung);
        $staemme = array_map([self::class, 'stamm'], $woerter);
        $punkte = [];
        $getroffen = [];   // Artikel-ID => [Wortnummer => true]
        foreach ($bestand['artikel'] as $id => $a) {
            $p = 0;
            foreach ($woerter as $i => $w) {
                $vorher = $p;
                $st = $staemme[$i];
                if ($a['kurz_n'] === $w) {
                    $p += 30;
                } elseif (str_contains($a['titel_n'], $st)) {
                    $p += 12;
                }
                foreach ($bestand['worte'][$id] ?? [] as [$wort, $art]) {
                    if (str_contains($wort, $st) || (str_contains($w, $wort) && mb_strlen($wort) >= 5)) {
                        $p += 8;
                        break;
                    }
                }
                if ($p > $vorher) {
                    $getroffen[$id][$i] = true;
                }
            }
            if ($p > 0) {
                $punkte[$id] = $p;
            }
        }
        // Volltext: Datenbank vergleicht ohne Akzente (utf8mb4_unicode_ci), also auch "Ubelkeit"
        foreach ($woerter as $i => $w) {
            $roh = self::rohwort($frage, $w, $staemme[$i]);
            foreach (Artikel::inSammlung($sammlung->kennung)->select('id')->andWhere(['like', 'inhalt', $roh])->column() as $id) {
                $punkte[(int)$id] = ($punkte[(int)$id] ?? 0) + 2;
                $getroffen[(int)$id][$i] = true;
            }
        }
        // Bei mehreren Woertern zaehlt, wie viele davon ein Artikel trifft: "nicht einschlafen"
        // soll Artikel uebers Einschlafen nach vorn bringen, nicht alle, in denen eines vorkommt.
        $anzahl = count($woerter);
        if ($anzahl > 1) {
            foreach ($punkte as $id => $p) {
                $punkte[$id] = (int)round($p * (count($getroffen[$id] ?? []) / $anzahl) ** 2);
            }
            $punkte = array_filter($punkte);
        }
        arsort($punkte);
        $ergebnis = [];
        foreach (array_slice($punkte, 0, self::MAX_TREFFER, true) as $id => $p) {
            $ergebnis[] = ['artikel' => $bestand['artikel'][$id], 'punkte' => $p];
        }
        return $ergebnis;
    }

    /** Wortstamm fuer den Vergleich: haeufige Endung weg, aber nie kuerzer als MIN_STAMM Zeichen. */
    public static function stamm(string $wort): string
    {
        foreach (self::ENDUNGEN as $endung) {
            if (str_ends_with($wort, $endung) && mb_strlen($wort) - mb_strlen($endung) >= self::MIN_STAMM) {
                return mb_substr($wort, 0, -mb_strlen($endung));
            }
        }
        return $wort;
    }

    /** Das Suchwort (gekuerzt auf den Stamm) so, wie es im Text stehen koennte -- mit Umlaut, falls so eingegeben. */
    private static function rohwort(string $frage, string $normal, string $stamm): string
    {
        $weg = mb_strlen($normal) - mb_strlen($stamm);
        foreach (preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($frage)) as $teil) {
            if (Slug::normal($teil) === $normal) {
                return $weg > 0 ? mb_substr($teil, 0, -$weg) : $teil;
            }
        }
        return $stamm;
    }

    /** @return array{artikel: array<int, array>, worte: array<int, array<int, array{0: string, 1: string}>>} */
    public static function bestand(string $sammlung): array
    {
        return Yii::$app->cache->getOrSet(['nexus-wissen-bestand', Darstellung::stand($sammlung), self::wortStand($sammlung)], function () use ($sammlung) {
            $artikel = [];
            foreach (Artikel::inSammlung($sammlung)->select(['id', 'slug', 'titel', 'kurztitel', 'thema', 'inhalt'])->orderBy('titel')->asArray()->all() as $z) {
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
            foreach (self::worte($sammlung)->select(['w.artikel_id', 'w.wort', 'w.art'])->all() as $z) {
                $worte[(int)$z['artikel_id']][] = [Slug::normal((string)$z['wort']), (string)$z['art']];
            }
            return ['artikel' => $artikel, 'worte' => $worte];
        }, 86400);
    }

    public static function wortStand(string $sammlung): string
    {
        return self::worte($sammlung)->max('w.id') . '|' . self::worte($sammlung)->count();
    }

    /** Schlagworte und Suchwoerter der Artikel einer Sammlung (Tabellenkuerzel w) */
    private static function worte(string $sammlung): \yii\db\Query
    {
        return (new \yii\db\Query())->from(Artikel::WORT_TABELLE . ' w')
            ->innerJoin(Artikel::tableName() . ' a', 'a.id = w.artikel_id')->where(['a.sammlung' => $sammlung]);
    }

    /** @return array<string, int> Schlagwort => Anzahl Artikel (die haeufigsten) */
    public static function haeufigeSchlagworte(string $sammlung, int $anzahl = 40): array
    {
        return Yii::$app->cache->getOrSet(['nexus-wissen-haeufig', self::wortStand($sammlung), $anzahl], function () use ($sammlung, $anzahl) {
            return array_map('intval', self::worte($sammlung)->select(['n' => 'COUNT(*)', 'wort' => 'w.wort'])
                ->andWhere(['w.art' => 'schlagwort'])->groupBy('w.wort')->having(['>', 'COUNT(*)', 1])
                ->orderBy(['n' => SORT_DESC, 'wort' => SORT_ASC])->limit($anzahl)->indexBy('wort')->column());
        }, 3600);
    }

    /** @return int[] Artikel-IDs mit diesem Schlagwort */
    public static function mitSchlagwort(string $sammlung, string $wort): array
    {
        return array_map('intval', self::worte($sammlung)->select('w.artikel_id')
            ->andWhere(['w.art' => 'schlagwort', 'w.wort' => $wort])->column());
    }

    /**
     * "Siehe auch": Artikel mit den meisten gemeinsamen Schlagworten.
     * @return array<int, array> hoechstens $anzahl Artikel aus bestand()
     */
    public static function verwandt(string $sammlung, int $artikelId, int $anzahl = 5): array
    {
        $bestand = self::bestand($sammlung);
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
