<?php

namespace nexus\modules\gesundheit\services;

/** Lesbare Adressteile aus deutschen Titeln: "Übelkeit: Ursachen" -> "uebelkeit-ursachen". */
class Slug
{
    private const ERSATZ = ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'ß' => 'ss'];

    public static function aus(string $text, int $max = 120): string
    {
        $text = mb_strtolower(strtr($text, self::ERSATZ));
        $text = (string)iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = trim((string)preg_replace('/[^a-z0-9]+/', '-', $text), '-');
        return rtrim(substr($text, 0, $max), '-');
    }

    /** Fuer Suchvergleiche: Kleinbuchstaben, Umlaute ausgeschrieben, ohne Satzzeichen. */
    public static function normal(string $text): string
    {
        $text = mb_strtolower(strtr($text, self::ERSATZ));
        return trim((string)preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text));
    }
}
