<?php

namespace nexus\modules\gesundheit\services;

use nexus\modules\gesundheit\models\Artikel;
use nexus\modules\gesundheit\models\Version;
use RuntimeException;

/**
 * Uebernimmt Artikel aus dem Redaktionsordner (Redaktion/manifest.json + Artikel/*.md).
 *
 * Regeln aus der Uebernahme-Anleitung der Redaktion (CLAUDE_CODE_AUFTRAG.md):
 * - nur Eintraege mit status "ueberarbeitet", deren artikelpfad auf eine vorhandene Datei zeigt
 * - Schluessel ist der VOLLE Original-Dateiname (G-Nummern kommen doppelt vor)
 * - wiederholbar: gleiche Datei -> dieselbe Seite; keine Dubletten
 * - im Portal geaenderte Artikel werden nicht ungeprueft ueberschrieben (nur gemeldet)
 * - Texte werden 1:1 uebernommen, nicht zusammengefasst oder veraendert
 */
class Importer
{
    /** @return array{neu: int, aktualisiert: int, unveraendert: int, uebersprungen: array<int, string>, konflikte: array<int, string>} */
    public static function lauf(string $ordner, bool $probe = false): array
    {
        $manifestDatei = rtrim($ordner, '/') . '/Redaktion/manifest.json';
        if (!is_file($manifestDatei)) {
            throw new RuntimeException("Manifest nicht gefunden: $manifestDatei");
        }
        $manifest = json_decode((string)file_get_contents($manifestDatei), true);
        if (!is_array($manifest)) {
            throw new RuntimeException('Manifest ist kein gueltiges JSON.');
        }

        $bericht = ['neu' => 0, 'aktualisiert' => 0, 'unveraendert' => 0, 'uebersprungen' => [], 'konflikte' => []];
        foreach ($manifest as $eintrag) {
            if (($eintrag['status'] ?? '') !== 'ueberarbeitet') {
                continue;
            }
            $pfad = rtrim($ordner, '/') . '/' . ltrim((string)($eintrag['artikelpfad'] ?? ''), '/');
            $schluessel = trim((string)($eintrag['original'] ?? ''));
            if ($schluessel === '' || !is_file($pfad) || !str_starts_with((string)$eintrag['artikelpfad'], 'Artikel/')) {
                $bericht['uebersprungen'][] = $schluessel . ' (Datei fehlt: ' . ($eintrag['artikelpfad'] ?? '-') . ')';
                continue;
            }
            $inhalt = str_replace("\r\n", "\n", (string)file_get_contents($pfad));
            $thema = (string)($eintrag['thema'] ?? '');
            if (!isset(Artikel::THEMEN[$thema])) {
                $bericht['uebersprungen'][] = "$schluessel (unbekanntes Thema $thema)";
                continue;
            }
            $titel = trim((string)($eintrag['titel'] ?? '')) ?: self::ersteUeberschrift($inhalt) ?: basename($pfad, '.md');
            $hash = hash('sha256', $inhalt);

            $artikel = Artikel::findOne(['schluessel' => $schluessel]);
            if ($artikel === null) {
                $artikel = new Artikel([
                    'schluessel' => $schluessel,
                    'gnummer' => self::gnummer($schluessel),
                    'datei' => basename($pfad),
                    'slug' => self::freierSlug($titel, $schluessel),
                ]);
                $grund = 'Import aus der Redaktion';
                $bericht['neu']++;
            } elseif ($artikel->import_hash === $hash) {
                $bericht['unveraendert']++;
                continue;
            } elseif ($artikel->im_portal_geaendert) {
                $bericht['konflikte'][] = "$titel: neue Fassung in der Redaktion, aber im Portal bearbeitet -- nicht ueberschrieben";
                continue;
            } else {
                $grund = 'Import: neue Fassung der Redaktion';
                $bericht['aktualisiert']++;
            }
            if ($probe) {
                continue;
            }
            $artikel->setAttributes([
                'titel' => mb_substr($titel, 0, 255),
                'kurztitel' => Artikel::kurztitelAus($titel),
                'thema' => $thema,
                'inhalt' => $inhalt,
                'pruefdatum' => self::datum($eintrag['pruefdatum'] ?? null),
                'wiedervorlage' => self::datum($eintrag['wiedervorlage'] ?? null),
            ], false);
            $artikel->datei = basename($pfad);
            $artikel->import_hash = $hash;
            if (!$artikel->save()) {
                throw new RuntimeException("$schluessel: " . json_encode($artikel->errors, JSON_UNESCAPED_UNICODE));
            }
            Version::speichern($artikel, $grund, null);
        }
        return $bericht;
    }

    private static function ersteUeberschrift(string $markdown): string
    {
        return preg_match('/^#\s+(.+)$/m', $markdown, $m) ? trim($m[1]) : '';
    }

    private static function gnummer(string $schluessel): ?string
    {
        return preg_match('/^(G\d{4}[a-z]?)/i', $schluessel, $m) ? strtoupper($m[1]) : null;
    }

    private static function datum($wert): ?string
    {
        $wert = trim((string)$wert);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $wert) ? $wert : null;
    }

    private static function freierSlug(string $titel, string $schluessel): string
    {
        $basis = Slug::aus(Artikel::kurztitelAus($titel), 100) ?: Slug::aus($schluessel, 100);
        $slug = $basis;
        $g = strtolower((string)self::gnummer($schluessel));
        for ($i = 2; Artikel::find()->where(['slug' => $slug])->exists(); $i++) {
            $slug = $basis . '-' . ($g !== '' && $i === 2 ? $g : $i);
        }
        return $slug;
    }
}
