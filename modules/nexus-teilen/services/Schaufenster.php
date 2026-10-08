<?php

namespace nexus\modules\teilen\services;

use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\file\models\File;
use nexus\modules\teilen\models\Freigabe;
use Throwable;
use Yii;
use yii\helpers\Url;

/**
 * Karten fuer die oeffentliche Seite "Aus der N.E.X.U.S.-Gemeinschaft" und den Block auf der
 * Gast-Startseite des Portals: Ueberschrift, Anfang des Textes, erstes Bild, Datum, Link zur
 * Leseseite. Alles anonymisiert (siehe Inhalt::anonymisieren) und in der Sprache des Besuchers.
 */
class Schaufenster
{
    public const JE_SEITE = 12;

    /** @return array<int, array{titel: string, text: string, bild: ?string, datum: string, link: string}> */
    public static function karten(string $sprache, int $anzahl, int $ab = 0): array
    {
        $karten = [];
        foreach (Freigabe::schaufenster($anzahl, $ab) as $freigabe) {
            $record = Inhalt::ausContentId((int)$freigabe->content_id);
            if ($record === null) {
                continue;
            }
            try {
                $karten[] = self::karte($freigabe, $record, $sprache);
            } catch (Throwable $e) {
                Yii::warning('nexus-teilen: Schaufenster-Karte fuer content#' . $freigabe->content_id . ' fehlt: ' . $e->getMessage(), 'nexus-teilen');
            }
        }
        return $karten;
    }

    public static function gibtAeltere(int $ab): bool
    {
        return Freigabe::schaufenster(1, $ab) !== [];
    }

    private static function karte(Freigabe $freigabe, ContentActiveRecord $record, string $sprache): array
    {
        $fassung = Inhalt::fassung($record, $sprache);
        $text = Inhalt::anonymisieren($fassung['text'], Texte::get('ein_mitglied', $sprache));
        [$titel, $kurz] = self::titelUndText($fassung['titel'], $text);
        $bild = self::bild($freigabe, $record, $text);
        $video = false;
        if ($bild === null) {
            $bild = self::videoStandbild($freigabe, $record);
            $video = $bild !== null;
        }
        return [
            'titel' => $titel,
            'text' => $kurz,
            'bild' => $bild,
            'video' => $video,
            'datum' => (string)$record->content->created_at,
            'link' => $freigabe->adresse($sprache),
        ];
    }

    /**
     * Beitraege haben keinen eigenen Titel -- die erste Zeile wird zum Titel, und der Text darunter
     * soll sie nicht wiederholen (Josh, 03.10.2026: Gartenapotheke stand doppelt da). Ist die erste
     * Zeile lang, wird der erste Satz (bis . : ! ?) zum Titel, der Rest ist Text.
     *
     * @return array{0: string, 1: string}
     */
    private static function titelUndText(string $eigenerTitel, string $text): array
    {
        $voll = Inhalt::kurztext($text, 2000);
        if ($eigenerTitel !== '') {
            return [$eigenerTitel, self::kuerzen($voll)];
        }
        $erste = Inhalt::ueberschrift('', $text, 2000);
        if (mb_strlen($erste) <= 90) {
            $titel = $erste;
            $schnitt = $erste;
        } elseif (preg_match('/^(.{10,90}?[.:!?])(?=\s)/u', $erste, $m)) {
            $titel = rtrim($m[1], ':');
            $schnitt = $m[1];
        } else {
            // Kein Satzende in Reichweite: an der letzten Wortgrenze schneiden, nie mitten im Wort.
            $schnitt = mb_substr($erste, 0, (int)mb_strrpos(mb_substr($erste, 0, 90), ' '));
            $titel = rtrim($schnitt, ' ,;-–') . ' …';
        }
        $rest = $voll;
        if (str_starts_with($rest, $schnitt)) {
            $rest = ltrim(mb_substr($rest, mb_strlen($schnitt)), " \t\n.:–-");
            if ($titel !== $schnitt && str_ends_with($titel, '…') && $rest !== '') {
                $rest = '… ' . $rest;
            }
        }
        return [$titel, self::kuerzen($rest)];
    }

    private static function kuerzen(string $text, int $max = 260): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $kurz = mb_substr($text, 0, $max);
        $leer = mb_strrpos($kurz, ' ');
        return rtrim($leer > $max * 0.6 ? mb_substr($kurz, 0, $leer) : $kurz, " ,;:.-") . '…';
    }

    /** Standbild des ersten Videos -- erzeugt es gleich mit, damit die Karte nicht ins Leere zeigt. */
    private static function videoStandbild(Freigabe $freigabe, ContentActiveRecord $record): ?string
    {
        $videos = File::find()
            ->where(['object_model' => get_class($record), 'object_id' => $record->id, 'show_in_stream' => 1])
            ->andWhere(['like', 'mime_type', 'video/%', false])
            ->orderBy(['id' => SORT_ASC])
            ->all();
        foreach ($videos as $video) {
            if (Standbild::pfad($video) !== null) {
                return Url::to(['/nexus-teilen/lesen/standbild', 't' => $freigabe->schluessel, 'guid' => $video->guid], true);
            }
        }
        return null;
    }

    /** Erstes Bild aus dem Text, sonst erstes angehaengtes Bild -- immer ueber die oeffentliche Datei-Adresse. */
    private static function bild(Freigabe $freigabe, ContentActiveRecord $record, string $text): ?string
    {
        $guids = [];
        if (preg_match_all('/file-guid:([a-f0-9-]{36})/i', $text, $m)) {
            $guids = $m[1];
        }
        $dateien = File::find()->where(['object_model' => get_class($record), 'object_id' => $record->id])->orderBy(['id' => SORT_ASC])->all();
        $bilder = array_filter($dateien, fn(File $d) => str_starts_with((string)$d->mime_type, 'image/'));
        foreach ($guids as $guid) {
            foreach ($bilder as $d) {
                if ($d->guid === $guid) {
                    return self::dateiAdresse($freigabe, $guid);
                }
            }
        }
        foreach ($bilder as $d) {
            if ((int)$d->show_in_stream === 1) {
                return self::dateiAdresse($freigabe, $d->guid);
            }
        }
        return null;
    }

    private static function dateiAdresse(Freigabe $freigabe, string $guid): string
    {
        return Url::to(['/nexus-teilen/lesen/datei', 't' => $freigabe->schluessel, 'guid' => $guid], true);
    }
}
