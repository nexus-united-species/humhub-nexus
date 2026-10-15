<?php

namespace nexus\modules\projekte\services;

use humhub\helpers\Html;

/**
 * Vorher/Nachher wortgenau: Gestrichenes rot (<del>), Neues gruen (<ins>).
 *
 * Gleicher Anfang und gleiches Ende werden zuerst abgeschnitten (die meisten Aenderungen sind
 * oertlich), nur der Teil dazwischen wird Wort fuer Wort verglichen. Ist er sehr lang, erscheint er
 * als Ganzes gestrichen bzw. neu -- richtig, nur weniger fein (schuetzt den Speicher des Servers).
 */
class Diff
{
    private const MAX_ZELLEN = 250000;

    public static function html(?string $alt, ?string $neu): string
    {
        $a = self::woerter((string)$alt);
        $b = self::woerter((string)$neu);
        $vorne = 0;
        while ($vorne < count($a) && $vorne < count($b) && $a[$vorne] === $b[$vorne]) {
            $vorne++;
        }
        $hinten = 0;
        while ($hinten < count($a) - $vorne && $hinten < count($b) - $vorne
            && $a[count($a) - 1 - $hinten] === $b[count($b) - 1 - $hinten]) {
            $hinten++;
        }
        $mitteA = array_slice($a, $vorne, count($a) - $vorne - $hinten);
        $mitteB = array_slice($b, $vorne, count($b) - $vorne - $hinten);

        $html = self::gleich(array_slice($a, 0, $vorne));
        if (count($mitteA) * count($mitteB) > self::MAX_ZELLEN) {
            $html .= self::markiert('del', $mitteA) . self::markiert('ins', $mitteB);
        } else {
            $html .= self::lcs($mitteA, $mitteB);
        }
        return $html . self::gleich(array_slice($a, count($a) - $hinten));
    }

    /**
     * Woerter, Leerraum und Satzzeichen als eigene Teile -- so bleibt bei "frei." -> "frei und mehr."
     * das "frei" stehen und nur das Neue wird markiert. Zeilenumbrueche bleiben erhalten.
     * @return string[]
     */
    private static function woerter(string $text): array
    {
        $text = str_replace("\r\n", "\n", $text);
        return $text === '' ? [] : preg_split('/(\s+|\p{P})/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    }

    private static function lcs(array $a, array $b): string
    {
        $n = count($a);
        $m = count($b);
        $l = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $l[$i][$j] = $a[$i] === $b[$j] ? $l[$i + 1][$j + 1] + 1 : max($l[$i + 1][$j], $l[$i][$j + 1]);
            }
        }
        $html = '';
        $weg = [];
        $dazu = [];
        $i = $j = 0;
        $leeren = function () use (&$html, &$weg, &$dazu) {
            $html .= self::markiert('del', $weg) . self::markiert('ins', $dazu);
            $weg = $dazu = [];
        };
        while ($i < $n && $j < $m) {
            if ($a[$i] === $b[$j]) {
                $leeren();
                $html .= self::gleich([$a[$i]]);
                $i++;
                $j++;
            } elseif ($l[$i + 1][$j] >= $l[$i][$j + 1]) {
                $weg[] = $a[$i++];
            } else {
                $dazu[] = $b[$j++];
            }
        }
        $weg = array_merge($weg, array_slice($a, $i));
        $dazu = array_merge($dazu, array_slice($b, $j));
        $leeren();
        return $html;
    }

    private static function gleich(array $teile): string
    {
        return nl2br(Html::encode(implode('', $teile)), false);
    }

    private static function markiert(string $tag, array $teile): string
    {
        $text = implode('', $teile);
        return trim($text) === '' ? Html::encode($text) : '<' . $tag . '>' . nl2br(Html::encode($text), false) . '</' . $tag . '>';
    }
}
