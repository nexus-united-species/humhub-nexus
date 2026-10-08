<?php

namespace nexus\modules\teilen\services;

use humhub\modules\file\models\File;
use Yii;

/**
 * Standbild aus einem Video -- als Kartenbild im Schaufenster, Vorschaubild beim Teilen und
 * "poster" vor dem Abspielen (Josh, 03.10.2026: Videonachrichten im Schaufenster). Wird beim
 * ersten Abruf einmal mit ffmpeg erzeugt und in @runtime aufgehoben; aendert sich das Video,
 * entsteht es neu (Pruefsumme der Datei im Namen).
 */
class Standbild
{
    private const FFMPEG = '/data/bin/ffmpeg';
    private const ORDNER = '@runtime/nexus-teilen-standbild';
    private const BREITE = 720;

    public static function istVideo(File $datei): bool
    {
        return str_starts_with((string)$datei->mime_type, 'video/');
    }

    /** Runde Videonachricht aus nexus-voice? Die zeigen wir auch oeffentlich rund. */
    public static function istVideonachricht(File $datei): bool
    {
        return str_starts_with((string)$datei->file_name, 'Videonachricht-');
    }

    /** Pfad zum Standbild (JPEG) oder null, wenn es sich nicht erzeugen laesst. */
    public static function pfad(File $datei): ?string
    {
        if (!self::istVideo($datei) || !is_file(self::FFMPEG)) {
            return null;
        }
        $quelle = realpath((string)$datei->store->get());
        if ($quelle === false || !is_file($quelle)) {
            return null;
        }
        $ordner = Yii::getAlias(self::ORDNER);
        if (!is_dir($ordner) && !@mkdir($ordner, 0775, true) && !is_dir($ordner)) {
            return null;
        }
        $ziel = $ordner . '/' . $datei->guid . '-' . substr((string)$datei->hash_sha1, 0, 12) . '.jpg';
        if (is_file($ziel) && filesize($ziel) > 0) {
            return $ziel;
        }
        // Erst bei 1 s versuchen (das allererste Bild ist oft schwarz), bei sehr kurzen Videos bei 0 s.
        foreach (['1', '0'] as $sekunde) {
            exec(sprintf(
                'timeout 30 %s -y -v error -ss %s -i %s -frames:v 1 -vf %s -q:v 4 %s 2>&1',
                escapeshellarg(self::FFMPEG),
                $sekunde,
                escapeshellarg($quelle),
                escapeshellarg('scale=' . self::BREITE . ':-2'),
                escapeshellarg($ziel)
            ), $ausgabe, $code);
            if ($code === 0 && is_file($ziel) && filesize($ziel) > 0) {
                return $ziel;
            }
        }
        Yii::warning('nexus-teilen: Standbild fuer file#' . $datei->id . ' nicht moeglich: ' . implode(' ', $ausgabe ?? []), 'nexus-teilen');
        @unlink($ziel);
        return null;
    }
}
