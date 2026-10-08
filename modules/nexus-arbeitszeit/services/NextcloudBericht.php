<?php

namespace nexus\modules\arbeitszeit\services;

use nexus\modules\arbeitszeit\models\Eintrag;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;
use Yii;
use yii\base\Application;

/**
 * Excel-Bericht in einem Nextcloud-Ordner (Einstellungen nextcloudUrl / nextcloudOrdner / nextcloudDatei).
 *
 * Die Datei ist ein SCHAUFENSTER, keine Eingabe: sie wird jedes Mal komplett aus dem Portal neu
 * geschrieben. Geaendert wird im Portal (Freigabe-Seite -> Bearbeiten), die Datei zieht nach.
 * Geschrieben mit dem technischen Nextcloud-Konto "nexus-portal", das nur diesen einen Ordner sieht
 * (App-Passwort in den Moduleinstellungen, nie im Code).
 *
 * Ausloeser: jede Aenderung (hoechstens EINMAL je Seitenaufruf, am Ende der Anfrage) und stuendlich
 * ueber HumHubs Zeitplaner -- dort nur, wenn sich seit dem letzten Hochladen etwas geaendert hat,
 * sonst saemmelten sich stuendlich unnoetige Dateiversionen in der Nextcloud an.
 * Ein Fehler beim Hochladen blockiert nie das Freigeben; der naechste Anlass holt es nach.
 */
class NextcloudBericht
{

    private static bool $vorgemerkt = false;

    /** Nach einer Aenderung: am Ende der Anfrage einmal hochladen. */
    public static function vormerken(): void
    {
        if (self::$vorgemerkt) {
            return;
        }
        self::$vorgemerkt = true;
        Yii::$app->on(Application::EVENT_AFTER_REQUEST, fn() => self::aktualisieren(true));
    }

    /** Stuendlich: nur hochladen, wenn sich etwas geaendert hat. */
    public static function stuendlich(): void
    {
        self::aktualisieren(false);
    }

    public static function aktualisieren(bool $erzwingen): bool
    {
        try {
            $modul = Yii::$app->getModule('nexus-arbeitszeit');
            $benutzer = (string)$modul->settings->get('nextcloudBenutzer');
            $passwort = (string)$modul->settings->get('nextcloudPasswort');
            if ($benutzer === '' || $passwort === '' || (string)$modul->nextcloudUrl === '') {
                return false;
            }
            $stand = md5(Eintrag::find()->count() . '|' . Eintrag::find()->max('updated_at'));
            if (!$erzwingen && $stand === $modul->settings->get('berichtStand')) {
                return true;
            }
            $datei = self::erstellen();
            $ok = self::hochladen($datei, $benutzer, $passwort);
            @unlink($datei);
            if ($ok) {
                $modul->settings->set('berichtStand', $stand);
                $modul->settings->set('berichtZuletzt', date('Y-m-d H:i:s'));
            }
            return $ok;
        } catch (Throwable $e) {
            Yii::error('nexus-arbeitszeit: Nextcloud-Bericht fehlgeschlagen: ' . $e->getMessage(), 'nexus-arbeitszeit');
            return false;
        }
    }

    /** Baut die Arbeitsmappe und gibt den Pfad einer temporaeren .xlsx-Datei zurueck. */
    public static function erstellen(): string
    {
        $alle = Eintrag::find()->with(['user', 'space', 'pruefer'])->where(['!=', 'status', Eintrag::STATUS_ENTWURF])
            ->orderBy(['datum' => SORT_DESC, 'id' => SORT_DESC])->all();
        $frei = array_filter($alle, fn(Eintrag $e) => $e->status === Eintrag::STATUS_FREIGEGEBEN);

        $mappe = new Spreadsheet();
        $mappe->getProperties()->setTitle('N.E.X.U.S. Arbeitsstunden')->setCreator('N.E.X.U.S. Portal');

        // 1. Uebersicht je Person
        $jePerson = [];
        foreach ($alle as $e) {
            $k = $e->user_id ? 'u' . $e->user_id : 'x' . $e->name_extern;
            $jePerson[$k] ??= ['name' => $e->name(), 'gesamt' => 0.0, 'monat' => 0.0, 'offen' => 0.0, 'eintraege' => 0];
            if ($e->status === Eintrag::STATUS_FREIGEGEBEN) {
                $jePerson[$k]['gesamt'] += (float)$e->stunden;
                $jePerson[$k]['eintraege']++;
                if ($e->datum >= date('Y-m-01')) {
                    $jePerson[$k]['monat'] += (float)$e->stunden;
                }
            } elseif ($e->status === Eintrag::STATUS_OFFEN) {
                $jePerson[$k]['offen'] += (float)$e->stunden;
            }
        }
        uasort($jePerson, fn($a, $b) => $b['gesamt'] <=> $a['gesamt']);
        $zeilen = [];
        foreach ($jePerson as $p) {
            $zeilen[] = [$p['name'], $p['gesamt'], $p['monat'], $p['offen'], $p['eintraege']];
        }
        $zeilen[] = ['Summe', array_sum(array_column($zeilen, 1)), array_sum(array_column($zeilen, 2)), array_sum(array_column($zeilen, 3)), array_sum(array_column($zeilen, 4))];
        self::blatt($mappe->getActiveSheet(), 'Übersicht',
            'Freigegebene Arbeitsstunden je Person – Stand ' . date('d.m.Y H:i') . ' Uhr',
            ['Name', 'Freigegeben gesamt (Std.)', 'Diesen Monat (Std.)', 'Wartet auf Freigabe (Std.)', 'Einträge'], $zeilen, [2, 3, 4]);

        // 2. Je Monat und Person
        $jeMonat = [];
        foreach ($frei as $e) {
            $m = substr($e->datum, 0, 7);
            $jeMonat[$m . '|' . $e->name()] = ($jeMonat[$m . '|' . $e->name()] ?? 0) + (float)$e->stunden;
        }
        krsort($jeMonat);
        $zeilen = [];
        foreach ($jeMonat as $schluessel => $h) {
            [$m, $name] = explode('|', $schluessel, 2);
            $zeilen[] = [date('m/Y', strtotime($m . '-01')), $name, $h];
        }
        self::blatt($mappe->createSheet(), 'Je Monat', 'Freigegebene Stunden je Monat und Person', ['Monat', 'Name', 'Stunden'], $zeilen, [3]);

        // 3. Je Kreis
        $jeKreis = [];
        foreach ($frei as $e) {
            $name = $e->space->name ?? '(ohne Kreis)';
            $jeKreis[$name] = ($jeKreis[$name] ?? 0) + (float)$e->stunden;
        }
        arsort($jeKreis);
        $zeilen = array_map(null, array_keys($jeKreis), array_values($jeKreis));
        self::blatt($mappe->createSheet(), 'Je Kreis', 'Freigegebene Stunden je Kreis', ['Kreis', 'Stunden'], $zeilen, [2]);

        // 4. Alle Eintraege
        $zeilen = [];
        foreach ($alle as $e) {
            $zeilen[] = [
                date('d.m.Y', strtotime($e->datum)), $e->name(), $e->space->name ?? '–', (float)$e->stunden, $e->beschreibung,
                ['offen' => 'wartet', 'freigegeben' => 'freigegeben', 'ruecksprache' => 'Rücksprache'][$e->status] ?? $e->status,
                ['assistent' => 'Assistent', 'formular' => 'Formular', 'telegram' => 'Telegram (übernommen)'][$e->quelle] ?? $e->quelle,
                $e->pruefer->displayName ?? '', $e->geprueft_am ? date('d.m.Y H:i', strtotime($e->geprueft_am)) : '',
                (string)$e->rueckfrage, (string)$e->verlauf,
            ];
        }
        self::blatt($mappe->createSheet(), 'Alle Einträge', 'Alle Einträge (neueste oben) – Änderungen bitte im Portal, nicht hier',
            ['Datum', 'Name', 'Kreis', 'Stunden', 'Tätigkeit', 'Status', 'Eingetragen über', 'Geprüft von', 'Geprüft am', 'Rückfrage', 'Änderungen'], $zeilen, [4]);

        $mappe->setActiveSheetIndex(0);
        $pfad = tempnam(sys_get_temp_dir(), 'naz') . '.xlsx';
        (new Xlsx($mappe))->save($pfad);
        return $pfad;
    }

    /** Ein Blatt: Titelzeile, Hinweis, fette Kopfzeile, Zahlenformat, feste Kopfzeile, passende Spaltenbreiten. */
    private static function blatt(Worksheet $blatt, string $name, string $titel, array $kopf, array $zeilen, array $zahlSpalten): void
    {
        $blatt->setTitle($name);
        $blatt->setCellValue('A1', $titel);
        $blatt->setCellValue('A2', 'Automatisch aus dem N.E.X.U.S. Portal erzeugt. Änderungen hier gehen beim nächsten Stand verloren – bitte im Portal ändern (Stunden freigeben → Bearbeiten).');
        $blatt->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $blatt->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('777777');
        $blatt->fromArray($kopf, null, 'A4');
        $letzte = chr(ord('A') + count($kopf) - 1);
        $blatt->getStyle("A4:{$letzte}4")->getFont()->setBold(true);
        $blatt->getStyle("A4:{$letzte}4")->getFill()->setFillType('solid')->getStartColor()->setRGB('F3E7B5');
        if ($zeilen) {
            $blatt->fromArray($zeilen, null, 'A5', true);
        }
        $ende = 4 + max(1, count($zeilen));
        foreach ($zahlSpalten as $nr) {
            $spalte = chr(ord('A') + $nr - 1);
            $blatt->getStyle("{$spalte}5:{$spalte}{$ende}")->getNumberFormat()->setFormatCode('#,##0.00');
        }
        $blatt->freezePane('A5');
        foreach (range('A', $letzte) as $spalte) {
            $blatt->getColumnDimension($spalte)->setAutoSize(true);
        }
        foreach (['E', 'K'] as $breit) { // Taetigkeit/Aenderungen nicht endlos breit
            if ($letzte >= $breit && count($kopf) > 5) {
                $blatt->getColumnDimension($breit)->setAutoSize(false)->setWidth(60);
                $blatt->getStyle("{$breit}5:{$breit}{$ende}")->getAlignment()->setWrapText(true);
            }
        }
    }

    private static function hochladen(string $datei, string $benutzer, string $passwort): bool
    {
        $modul = Yii::$app->getModule('nexus-arbeitszeit');
        $url = rtrim((string)$modul->nextcloudUrl, '/') . '/remote.php/dav/files/' . rawurlencode($benutzer)
            . '/' . rawurlencode((string)$modul->nextcloudOrdner) . '/' . rawurlencode((string)$modul->nextcloudDatei);
        $fh = fopen($datei, 'rb');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $fh,
            CURLOPT_INFILESIZE => filesize($datei),
            CURLOPT_USERPWD => $benutzer . ':' . $passwort,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $fehler = curl_error($ch);
        curl_close($ch);
        fclose($fh);
        if (!in_array($code, [200, 201, 204], true)) {
            Yii::error("nexus-arbeitszeit: Hochladen nach Nextcloud: HTTP $code $fehler", 'nexus-arbeitszeit');
            return false;
        }
        return true;
    }
}
