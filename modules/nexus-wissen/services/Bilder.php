<?php

namespace nexus\modules\wissen\services;

use RuntimeException;
use Yii;

/**
 * Bilder in Artikeln: aus Word-Dateien uebernommen oder im Editor eingefuegt.
 *
 * - Gespeichert je Sammlung in einem Ordner AUSSERHALB des oeffentlichen Web-Verzeichnisses
 *   (Vorgabe: neben dem Einstellungsordner, z. B. /data/nexus-wissen-bilder) -- ausgeliefert nur ueber
 *   die Seite "bild" der Sammlung, also nur an Menschen, die die Sammlung lesen duerfen.
 * - Dateiname = Pruefsumme des Inhalts: dasselbe Bild zweimal hochgeladen liegt nur einmal da.
 * - Nur echte Rasterbilder (PNG, JPEG, GIF, WebP) -- geprueft am Inhalt, nicht am Namen. SVG bewusst
 *   nicht: es kann Skripte enthalten.
 * - Sehr grosse Bilder werden auf MAX_BREITE verkleinert (Fotos vom Handy haben oft 4000 px).
 * Im Text steht ein Bild als ![Beschreibung](bilder/<datei>); die Darstellung macht daraus die Adresse.
 */
class Bilder
{
    public const PRAEFIX = 'bilder/';
    public const MAX_BYTES = 15 * 1024 * 1024;
    private const MAX_BREITE = 1600;
    private const TYPEN = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    public const MIME = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp'];
    private const NAME_MUSTER = '/^[a-f0-9]{40}\.(png|jpg|gif|webp)$/';

    /** Ordner fuer alle Sammlungen (Einstellung "bilderPfad" des Moduls, sonst neben dem Einstellungsordner). */
    public static function wurzel(): string
    {
        $modul = \nexus\modules\wissen\Module::instanz();
        $pfad = $modul !== null && $modul->bilderPfad !== '' ? (string)Yii::getAlias($modul->bilderPfad)
            : dirname((string)Yii::getAlias('@config')) . '/nexus-wissen-bilder';
        return rtrim($pfad, '/');
    }

    /**
     * Speichert ein Bild aus Bytes. Gibt den Dateinamen zurueck oder null, wenn es kein unterstuetztes Bild ist.
     * @throws RuntimeException wenn der Ordner nicht beschreibbar ist
     */
    public static function speichern(Sammlung $sammlung, string $bytes): ?string
    {
        if ($bytes === '' || strlen($bytes) > self::MAX_BYTES) {
            return null;
        }
        $info = @getimagesizefromstring($bytes);
        if ($info === false || !isset(self::TYPEN[$info[2]])) {
            return null;
        }
        $endung = self::TYPEN[$info[2]];
        $bytes = self::verkleinern($bytes, (int)$info[0], (int)$info[1], $endung);
        $ordner = self::wurzel() . '/' . $sammlung->kennung;
        if (!is_dir($ordner) && !@mkdir($ordner, 0750, true) && !is_dir($ordner)) {
            throw new RuntimeException('bilder_ordner');
        }
        $name = sha1($bytes) . '.' . $endung;
        if (!is_file("$ordner/$name") && @file_put_contents("$ordner/$name", $bytes, LOCK_EX) === false) {
            throw new RuntimeException('bilder_ordner');
        }
        return $name;
    }

    /** Voller Pfad eines Bildes der Sammlung -- null bei ungueltigem Namen oder fehlender Datei. */
    public static function pfad(Sammlung $sammlung, string $name): ?string
    {
        if (!preg_match(self::NAME_MUSTER, $name)) {
            return null;
        }
        $pfad = self::wurzel() . '/' . $sammlung->kennung . '/' . $name;
        return is_file($pfad) ? $pfad : null;
    }

    public static function markdown(string $name, string $beschreibung = ''): string
    {
        $text = trim(str_replace(['[', ']', "\n"], ['(', ')', ' '], $beschreibung));
        return '![' . $text . '](' . self::PRAEFIX . $name . ')';
    }

    /** Name aus einer Adresse im Text ("bilder/abc.png") -- null, wenn es kein Bild der Sammlung ist. */
    public static function nameAus(string $src): ?string
    {
        if (!str_starts_with($src, self::PRAEFIX)) {
            return null;
        }
        $name = substr($src, strlen(self::PRAEFIX));
        return preg_match(self::NAME_MUSTER, $name) ? $name : null;
    }

    private static function istGedreht(string $bytes): bool
    {
        if (!function_exists('exif_read_data')) {
            return false;
        }
        $exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($bytes));
        return in_array((int)($exif['Orientation'] ?? 1), [3, 6, 8], true);
    }

    private static function verkleinern(string $bytes, int $breite, int $hoehe, string $endung): string
    {
        // GIF koennten bewegt sein -- unveraendert lassen. Kleine Bilder ausser JPEG (Drehung) auch.
        if ($endung === 'gif' || ($breite <= self::MAX_BREITE && $endung !== 'jpg') || !function_exists('imagecreatefromstring')) {
            return $bytes;
        }
        if ($breite <= self::MAX_BREITE && $endung === 'jpg' && !self::istGedreht($bytes)) {
            return $bytes;
        }
        $bild = @imagecreatefromstring($bytes);
        if ($bild === false) {
            return $bytes;
        }
        // Handyfotos stehen oft nur per EXIF-Angabe aufrecht -- beim Verkleinern geht die Angabe verloren
        if ($endung === 'jpg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($bytes));
            $winkel = [3 => 180, 6 => -90, 8 => 90][(int)($exif['Orientation'] ?? 1)] ?? 0;
            if ($winkel !== 0 && ($gedreht = imagerotate($bild, $winkel, 0)) !== false) {
                $bild = $gedreht;
                [$breite, $hoehe] = [imagesx($bild), imagesy($bild)];
            }
        }
        if ($breite <= self::MAX_BREITE) {
            $klein = $bild;
        } else {
            $klein = imagescale($bild, self::MAX_BREITE, (int)round($hoehe * self::MAX_BREITE / $breite), IMG_BICUBIC);
        }
        if ($klein === false) {
            return $bytes;
        }
        if ($endung === 'png' || $endung === 'webp') {
            imagealphablending($klein, false);
            imagesavealpha($klein, true);
        }
        ob_start();
        $ok = match ($endung) {
            'png' => imagepng($klein, null, 6),
            'webp' => imagewebp($klein, null, 85),
            default => imagejpeg($klein, null, 85),
        };
        $neu = (string)ob_get_clean();
        return $ok && $neu !== '' ? $neu : $bytes;
    }
}
