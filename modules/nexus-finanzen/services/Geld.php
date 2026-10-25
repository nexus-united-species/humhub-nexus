<?php

namespace nexus\modules\finanzen\services;

use Yii;

/** Betraege liegen in Cent vor; hier nur Anzeige und Eingabe. */
class Geld
{
    public static function zeigen(int $cent, bool $mitCent = false): string
    {
        $euro = $cent / 100;
        $stellen = $mitCent || $cent % 100 !== 0 ? 2 : 0;
        return substr((string)Yii::$app->language, 0, 2) === 'en'
            ? '€' . number_format($euro, $stellen, '.', ',')
            : number_format($euro, $stellen, ',', '.') . ' €';
    }

    /** "1.234,56", "1234.56", "1234" -> Cent; null bei unlesbarer Eingabe */
    public static function lesen(string $text): ?int
    {
        $t = str_replace([' ', '€', "\u{00A0}"], '', trim($text));
        if ($t === '') {
            return null;
        }
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $t) || preg_match('/^\d+,\d{1,2}$/', $t)) {
            $t = str_replace(['.', ','], ['', '.'], $t);
        } elseif (preg_match('/^\d{1,3}(,\d{3})+(\.\d{1,2})?$/', $t)) {
            $t = str_replace(',', '', $t);
        }
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $t)) {
            return null;
        }
        return (int)round((float)$t * 100);
    }

    public static function monat(?string $monat): string
    {
        return is_string($monat) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monat) ? $monat : date('Y-m');
    }

    public static function vorMonat(string $monat): string
    {
        return date('Y-m', strtotime($monat . '-01 -1 month'));
    }

    public static function nachMonat(string $monat): string
    {
        return date('Y-m', strtotime($monat . '-01 +1 month'));
    }

    public static function monatName(string $monat): string
    {
        return Yii::$app->formatter->asDate($monat . '-01', 'LLLL yyyy');
    }
}
