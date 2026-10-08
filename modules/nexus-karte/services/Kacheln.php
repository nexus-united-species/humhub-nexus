<?php

namespace nexus\modules\karte\services;

use Yii;

/**
 * Kartenbilder ("Kacheln") von OpenStreetMap, ueber unseren Server geholt und hier
 * zwischengespeichert. Grund: Holte der Browser sie direkt, saehe OpenStreetMap, wer sich die
 * Karte ansieht. Der Zwischenspeicher ist ausserdem Bedingung der OSM-Nutzungsregeln.
 *
 * Der Speicher liegt unter @runtime und darf jederzeit verloren gehen (z. B. nach einem
 * Container-Neubau) -- er fuellt sich von selbst wieder.
 */
class Kacheln
{
    /** Weltuebersicht bis Ortsebene. Naeher heran geht es bewusst nicht (nur Ort, nie Adresse). */
    public const MIN_ZOOM = 2;
    public const MAX_ZOOM = 11;

    private const QUELLE = 'https://tile.openstreetmap.org/%d/%d/%d.png';
    private const HALTBAR_S = 30 * 24 * 3600;
    private const ZEITLIMIT_S = 8;
    private const PNG_KENNUNG = "\x89PNG";

    public static function gueltig(int $z, int $x, int $y): bool
    {
        if ($z < self::MIN_ZOOM || $z > self::MAX_ZOOM) {
            return false;
        }
        $max = (1 << $z) - 1;
        return $x >= 0 && $x <= $max && $y >= 0 && $y <= $max;
    }

    /** Pfad zur Kachel-Datei; null, wenn sie nicht zu bekommen ist. */
    public static function datei(int $z, int $x, int $y): ?string
    {
        if (!self::gueltig($z, $x, $y)) {
            return null;
        }
        $pfad = Yii::getAlias('@runtime') . "/nexus-karte/kacheln/$z/$x/$y.png";
        $vorhanden = is_file($pfad);
        if ($vorhanden && filemtime($pfad) > time() - self::HALTBAR_S) {
            return $pfad;
        }
        $bild = self::holen($z, $x, $y);
        if ($bild === null) {
            // Lieber ein altes Kartenbild als eine Luecke in der Karte.
            return $vorhanden ? $pfad : null;
        }
        $ordner = dirname($pfad);
        if (!is_dir($ordner) && !@mkdir($ordner, 0775, true) && !is_dir($ordner)) {
            Yii::error("nexus-karte: Ordner $ordner laesst sich nicht anlegen", 'nexus-karte');
            return null;
        }
        // Erst daneben schreiben, dann umbenennen: nie eine halbe Datei ausliefern.
        $vorlaeufig = $pfad . '.' . getmypid() . '.tmp';
        if (file_put_contents($vorlaeufig, $bild) === false || !rename($vorlaeufig, $pfad)) {
            @unlink($vorlaeufig);
            Yii::error("nexus-karte: Kachel $pfad laesst sich nicht speichern", 'nexus-karte');
            return null;
        }
        return $pfad;
    }

    private static function holen(int $z, int $x, int $y): ?string
    {
        $ch = curl_init(sprintf(self::QUELLE, $z, $x, $y));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::ZEITLIMIT_S,
            CURLOPT_USERAGENT => \nexus\modules\karte\Module::instanz()->absender(),
        ]);
        $bild = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($status !== 200 || !is_string($bild) || !str_starts_with($bild, self::PNG_KENNUNG)) {
            Yii::warning("nexus-karte: Kachel $z/$x/$y nicht erhalten (HTTP $status)", 'nexus-karte');
            return null;
        }
        return $bild;
    }
}
