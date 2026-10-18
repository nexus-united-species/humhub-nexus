<?php

namespace nexus\modules\wissen\services;

use FilesystemIterator;
use nexus\modules\wissen\models\Artikel;
use nexus\modules\wissen\models\Version;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Uebernimmt die Artikel einer Sammlung aus ihrem Ordner (Einstellung "importPfad").
 *
 * Zwei Wege:
 * 1. Nur Markdown-Dateien: jede *.md-Datei (auch in Unterordnern) wird ein Artikel; der erste
 *    Unterordner ist das Thema ("Rezepte/Suppen/Linsensuppe.md" -> Thema "Rezepte"). README.md und
 *    Dateien/Ordner, die mit "_" oder "." beginnen, werden uebergangen.
 * 2. Mit manifest.json (im Ordner oder in Redaktion/): eine Liste von Eintraegen mit "artikelpfad"
 *    (oder "pfad") und optional "schluessel"/"original", "titel", "thema", "pruefdatum",
 *    "wiedervorlage", "kennung", "schlagworte", "suchwoerter", "status". Nur gelistete Dateien
 *    werden uebernommen; mit der Einstellung "importStatus" nur Eintraege mit diesem Status.
 *
 * Angaben koennen auch als Kopf am Anfang der Datei stehen (Front Matter), z. B.
 *   ---
 *   titel: Linsensuppe
 *   thema: Suppen
 *   schlagworte: [vegan, schnell]
 *   ---
 * Angaben aus dem Manifest gehen vor.
 *
 * Grundregeln:
 * - wiederholbar: dieselbe Datei ergibt immer denselben Artikel, keine Dubletten
 * - unveraenderte Dateien werden uebersprungen (Pruefsumme)
 * - im Portal bearbeitete Artikel werden nie ueberschrieben, nur gemeldet
 * - Artikel, deren Datei verschwunden ist, werden gemeldet, nicht geloescht
 * - der Text wird 1:1 uebernommen
 */
class Importer
{
    private const FELDER = [
        'titel' => ['titel', 'title'],
        'thema' => ['thema', 'topic'],
        'kennung' => ['kennung', 'id'],
        'pruefdatum' => ['pruefdatum', 'reviewed'],
        'wiedervorlage' => ['wiedervorlage', 'next_review'],
        'schlagworte' => ['schlagworte', 'keywords', 'tags'],
        'suchwoerter' => ['suchwoerter', 'search_terms'],
    ];

    /**
     * @param bool $erzwingen auch unveraenderte Dateien neu uebernehmen (z. B. nach Aenderungen nur im Manifest)
     * @return array{neu: int, aktualisiert: int, unveraendert: int, uebersprungen: string[], konflikte: string[], verwaist: string[]}
     */
    public static function lauf(Sammlung $sammlung, bool $probe = false, bool $erzwingen = false): array
    {
        $ordner = realpath($sammlung->importPfad());
        if ($sammlung->importPfad() === '' || $ordner === false || !is_dir($ordner)) {
            throw new RuntimeException('Import-Ordner nicht gefunden: ' . ($sammlung->importPfad() ?: '(Einstellung "importPfad" fehlt)'));
        }
        $bericht = ['neu' => 0, 'aktualisiert' => 0, 'unveraendert' => 0, 'uebersprungen' => [], 'konflikte' => [], 'verwaist' => []];
        $gesehen = [];

        foreach (self::eintraege($ordner, $sammlung, $bericht) as $eintrag) {
            $pfad = realpath($ordner . '/' . $eintrag['pfad']);
            if ($pfad === false || !is_file($pfad) || !str_starts_with($pfad, $ordner . DIRECTORY_SEPARATOR)) {
                $bericht['uebersprungen'][] = $eintrag['schluessel'] . ' (Datei fehlt: ' . $eintrag['pfad'] . ')';
                continue;
            }
            $roh = str_replace("\r\n", "\n", (string)file_get_contents($pfad));
            [$kopf, $inhalt] = self::frontMatter($roh);
            $meta = $eintrag['meta'] + $kopf;
            if (trim($inhalt) === '') {
                $bericht['uebersprungen'][] = $eintrag['schluessel'] . ' (leer)';
                continue;
            }
            $gesehen[$eintrag['schluessel']] = true;
            $titel = trim((string)($meta['titel'] ?? '')) ?: self::ersteUeberschrift($inhalt) ?: Sammlung::nameAusCode(basename($pfad, '.md'));
            $hash = hash('sha256', $roh);

            $artikel = Artikel::findOne(['sammlung' => $sammlung->kennung, 'schluessel' => $eintrag['schluessel']]);
            if ($artikel === null) {
                $artikel = new Artikel(['sammlung' => $sammlung->kennung, 'schluessel' => $eintrag['schluessel']]);
                $grund = 'Import';
                $bericht['neu']++;
            } elseif ($artikel->import_hash === $hash && !$erzwingen) {
                $bericht['unveraendert']++;
                continue;
            } elseif ($artikel->im_portal_geaendert) {
                $bericht['konflikte'][] = "$titel: neue Fassung im Ordner, aber im Portal bearbeitet -- nicht ueberschrieben";
                continue;
            } else {
                $grund = 'Import: neue Fassung';
                $bericht['aktualisiert']++;
            }
            if ($probe) {
                continue;
            }
            $kennung = trim((string)($meta['kennung'] ?? '')) ?: self::kennungAus(basename($eintrag['schluessel']));
            if ($artikel->isNewRecord) {
                $artikel->slug = Artikel::freierSlug($sammlung->kennung, $titel, (string)$kennung, $eintrag['schluessel']);
            }
            $artikel->setAttributes([
                'kennung' => $kennung !== '' ? mb_substr($kennung, 0, 40) : null,
                'datei' => mb_substr(basename($pfad), 0, 190),
                'titel' => mb_substr($titel, 0, 255),
                'kurztitel' => Artikel::kurztitelAus($titel),
                'thema' => mb_substr(trim((string)($meta['thema'] ?? '')), 0, 100),
                'inhalt' => $inhalt,
                'pruefdatum' => self::datum($meta['pruefdatum'] ?? null),
                'wiedervorlage' => self::datum($meta['wiedervorlage'] ?? null),
            ], false);
            $artikel->import_hash = $hash;
            if (!$artikel->save()) {
                throw new RuntimeException($eintrag['schluessel'] . ': ' . json_encode($artikel->errors, JSON_UNESCAPED_UNICODE));
            }
            foreach (['schlagworte' => 'schlagwort', 'suchwoerter' => 'suchwort'] as $feld => $art) {
                if (isset($meta[$feld])) {
                    $artikel->setzeWoerter($art, self::liste($meta[$feld]));
                }
            }
            Version::speichern($artikel, $grund, null);
        }

        foreach (Artikel::inSammlung($sammlung->kennung)->select(['schluessel', 'titel'])->asArray()->all() as $z) {
            // Im Portal geschriebene Artikel haben keine Datei -- sie fehlen im Ordner nicht
            if (!isset($gesehen[$z['schluessel']]) && !Artikel::ausDemPortal($z['schluessel'])) {
                $bericht['verwaist'][] = $z['titel'] . ' (' . $z['schluessel'] . ')';
            }
        }
        return $bericht;
    }

    /** @return array<int, array{pfad: string, schluessel: string, meta: array}> */
    private static function eintraege(string $ordner, Sammlung $sammlung, array &$bericht): array
    {
        foreach (['manifest.json', 'Redaktion/manifest.json'] as $name) {
            if (is_file("$ordner/$name")) {
                return self::ausManifest("$ordner/$name", $sammlung, $bericht);
            }
        }
        $liste = [];
        $dateien = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ordner, FilesystemIterator::SKIP_DOTS));
        foreach ($dateien as $datei) {
            $relativ = str_replace(DIRECTORY_SEPARATOR, '/', substr($datei->getPathname(), strlen($ordner) + 1));
            $teile = explode('/', $relativ);
            if (strtolower($datei->getExtension()) !== 'md' || strtolower($datei->getFilename()) === 'readme.md') {
                continue;
            }
            if (array_filter($teile, fn($t) => $t[0] === '_' || $t[0] === '.') !== []) {
                continue;
            }
            $liste[] = ['pfad' => $relativ, 'schluessel' => $relativ, 'meta' => count($teile) > 1 ? ['thema' => $teile[0]] : []];
        }
        usort($liste, fn($a, $b) => strcmp($a['pfad'], $b['pfad']));
        return $liste;
    }

    private static function ausManifest(string $datei, Sammlung $sammlung, array &$bericht): array
    {
        $manifest = json_decode((string)file_get_contents($datei), true);
        if (!is_array($manifest)) {
            throw new RuntimeException("Manifest ist kein gueltiges JSON: $datei");
        }
        $manifest = $manifest['artikel'] ?? $manifest;
        $status = $sammlung->importStatus();
        $liste = [];
        foreach ($manifest as $e) {
            if (!is_array($e) || ($status !== null && ($e['status'] ?? null) !== $status)) {
                continue;
            }
            $pfad = ltrim(str_replace('\\', '/', (string)($e['artikelpfad'] ?? $e['pfad'] ?? '')), '/');
            $schluessel = trim((string)($e['schluessel'] ?? $e['original'] ?? '')) ?: $pfad;
            if ($pfad === '' || $schluessel === '') {
                $bericht['uebersprungen'][] = 'Manifest-Eintrag ohne Pfad: ' . mb_substr(json_encode($e, JSON_UNESCAPED_UNICODE), 0, 120);
                continue;
            }
            $meta = [];
            foreach (array_keys(self::FELDER) as $feld) {
                if (isset($e[$feld]) && $e[$feld] !== '') {
                    $meta[$feld] = $e[$feld];
                }
            }
            if (!isset($meta['thema']) && str_contains($pfad, '/')) {
                $meta['thema'] = basename(dirname($pfad));
            }
            $liste[] = ['pfad' => $pfad, 'schluessel' => mb_substr($schluessel, 0, 255), 'meta' => $meta];
        }
        return $liste;
    }

    /**
     * Kopf "--- schluessel: wert ---" am Dateianfang lesen und vom Text trennen.
     * @return array{0: array<string, mixed>, 1: string}
     */
    public static function frontMatter(string $text): array
    {
        if (!preg_match('/\A---\n(.*?)\n---\n?/s', $text, $m)) {
            return [[], $text];
        }
        $roh = [];
        foreach (explode("\n", $m[1]) as $zeile) {
            if (preg_match('/^\s*([A-Za-z_]+)\s*:\s*(.*)$/', $zeile, $z)) {
                $roh[strtolower($z[1])] = trim($z[2], " \t\"'");
            }
        }
        $meta = [];
        foreach (self::FELDER as $feld => $namen) {
            foreach ($namen as $name) {
                if (isset($roh[$name]) && $roh[$name] !== '') {
                    $meta[$feld] = $roh[$name];
                    break;
                }
            }
        }
        return [$meta, substr($text, strlen($m[0]))];
    }

    /** "[a, b]", "a, b" oder ein Array -> ['a', 'b'] */
    private static function liste($wert): array
    {
        if (is_array($wert)) {
            return array_map('strval', $wert);
        }
        return array_map(fn($w) => trim($w, " \t\"'"), explode(',', trim((string)$wert, '[] ')));
    }

    private static function ersteUeberschrift(string $markdown): string
    {
        return preg_match('/^#\s+(.+)$/m', $markdown, $m) ? trim($m[1]) : '';
    }

    /** Fuehrende Kennung aus dem Dateinamen, z. B. "G0066A Kamille.doc" -> "G0066A" */
    private static function kennungAus(string $dateiname): string
    {
        return preg_match('/^([A-Za-z]{1,3}\d{3,}[a-z]?)(?![A-Za-z0-9])/i', $dateiname, $m) ? strtoupper($m[1]) : '';
    }

    private static function datum($wert): ?string
    {
        $wert = trim((string)$wert);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $wert) ? $wert : null;
    }
}
