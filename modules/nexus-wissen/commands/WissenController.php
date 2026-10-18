<?php

namespace nexus\modules\wissen\commands;

use nexus\modules\wissen\models\Artikel;
use nexus\modules\wissen\Module;
use nexus\modules\wissen\services\Importer;
use nexus\modules\wissen\services\Sammlung;
use nexus\modules\wissen\services\Schlagworte;
use Throwable;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Wissenssammlungen auf der Konsole (im HumHub-Verzeichnis protected/, als Web-Server-Benutzer):
 *
 *   php yii wissen/liste                               alle Sammlungen mit Artikelzahl
 *   php yii wissen/import <sammlung> --probe           zeigen, was der Import tun wuerde
 *   php yii wissen/import <sammlung>                   Artikel uebernehmen
 *   php yii wissen/import <sammlung> --alle            auch unveraenderte Dateien neu uebernehmen
 *   php yii wissen/schlagworte <sammlung> --probe=3    KI-Schlagworte fuer 3 Artikel zeigen
 *   php yii wissen/schlagworte <sammlung>              fuer alle Artikel ohne Schlagworte
 *   php yii wissen/schlagworte <sammlung> --neu        fuer alle Artikel neu
 */
class WissenController extends Controller
{
    /**
     * @var string|bool|null Nur zeigen, nichts speichern (bei schlagworte: Anzahl Artikel, Vorgabe 3).
     * Bewusst null statt false: Yii wandelt einen Schalter in den Typ der Vorgabe um -- "--probe=3"
     * wuerde bei false zu true.
     */
    public $probe = null;

    /** @var bool import: auch unveraenderte Dateien neu uebernehmen */
    public $alle = false;

    /** @var bool schlagworte: auch Artikel neu verschlagworten, die schon Schlagworte haben */
    public $neu = false;

    private const KI_VERSUCHE = 3;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), match ($actionID) {
            'import' => ['probe', 'alle'],
            'schlagworte' => ['probe', 'neu'],
            default => [],
        });
    }

    public function actionListe(): int
    {
        $modul = $this->modul();
        if ($modul === null) {
            return ExitCode::UNAVAILABLE;
        }
        if ($modul->alleSammlungen() === []) {
            $this->stdout("Keine Sammlung eingestellt (common.php: 'modules' => ['nexus-wissen' => ['sammlungen' => [...]]]).\n");
        }
        foreach ($modul->alleSammlungen() as $s) {
            $kreis = $s->kreis();
            printf("%-20s %-30s Kreis %s, %d Artikel, %s\n", $s->kennung, $s->name(), $kreis ? "{$kreis->id} ({$kreis->name})" : "{$s->kreisId()} (nicht gefunden!)",
                Artikel::inSammlung($s->kennung)->count(), $s->freigegeben() ? 'freigegeben' : 'noch nicht freigegeben');
        }
        return ExitCode::OK;
    }

    public function actionImport(string $sammlung): int
    {
        $s = $this->sammlung($sammlung);
        if ($s === null) {
            return ExitCode::DATAERR;
        }
        try {
            $bericht = Importer::lauf($s, $this->istProbe(), (bool)$this->alle);
        } catch (Throwable $e) {
            $this->stderr('Import abgebrochen: ' . $e->getMessage() . "\n");
            return ExitCode::DATAERR;
        }
        Yii::$app->cache->flush();
        $this->stdout(($this->istProbe() ? 'PROBE -- nichts gespeichert' : 'Import abgeschlossen') . " ({$s->kennung})\n");
        printf("neu: %d, aktualisiert: %d, unveraendert: %d, uebersprungen: %d, nicht ueberschrieben: %d, Datei verschwunden: %d\n",
            $bericht['neu'], $bericht['aktualisiert'], $bericht['unveraendert'], count($bericht['uebersprungen']),
            count($bericht['konflikte']), count($bericht['verwaist']));
        foreach (['uebersprungen' => 'uebersprungen', 'konflikte' => 'nicht ueberschrieben', 'verwaist' => 'Datei verschwunden (Artikel bleibt)'] as $feld => $text) {
            foreach ($bericht[$feld] as $zeile) {
                $this->stdout("  $text: $zeile\n");
            }
        }
        return ExitCode::OK;
    }

    public function actionSchlagworte(string $sammlung): int
    {
        $s = $this->sammlung($sammlung);
        if ($s === null) {
            return ExitCode::DATAERR;
        }
        if (!Schlagworte::verfuegbar()) {
            $this->stderr("Fuer KI-Schlagworte wird das Modul nexus-community-assistant (mit nexus-translate) gebraucht.\n");
            return ExitCode::UNAVAILABLE;
        }
        $anzahlProbe = !$this->istProbe() ? 0 : ($this->probe === true ? 3 : max(1, (int)$this->probe));

        $abfrage = Artikel::inSammlung($s->kennung)->orderBy('id');
        if (!$this->neu) {
            $abfrage->andWhere(['not exists', (new \yii\db\Query())->from(Artikel::WORT_TABELLE . ' w')
                ->where('w.artikel_id = ' . Artikel::tableName() . '.id')->andWhere(['w.art' => 'schlagwort'])]);
        }
        if ($anzahlProbe > 0) {
            $abfrage->limit($anzahlProbe);
        }
        $alle = $abfrage->all();
        $gesamt = count($alle);
        $this->stdout("$gesamt Artikel" . ($anzahlProbe ? ' (PROBE, nichts gespeichert)' : '') . "\n");

        $fertig = 0;
        $fehler = 0;
        foreach ($alle as $artikel) {
            $daten = Schlagworte::erzeugen($s, $artikel, self::KI_VERSUCHE, fn($zeile) => $this->stdout($zeile . "\n"));
            if ($daten === null) {
                $fehler++;
                $this->stdout("FEHLER  {$artikel->titel}\n");
                continue;
            }
            $schlag = $daten['schlagworte'];
            $such = $daten['suchwoerter'];
            if ($anzahlProbe) {
                $this->stdout("\n{$artikel->titel}\n  Schlagworte: " . implode(', ', $schlag) . "\n  Suchwoerter: " . implode(', ', $such) . "\n");
                continue;
            }
            $artikel->setzeWoerter('schlagwort', $schlag);
            $artikel->setzeWoerter('suchwort', $such);
            $fertig++;
            if ($fertig % 25 === 0) {
                $this->stdout("$fertig / $gesamt erledigt\n");
            }
        }
        Yii::$app->cache->flush();
        $this->stdout("fertig: $fertig, fehlgeschlagen: $fehler\n");
        return $fehler > 0 ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }

    private function istProbe(): bool
    {
        return $this->probe !== null && $this->probe !== false && $this->probe !== '0';
    }

    private function modul(): ?Module
    {
        $modul = Module::instanz();
        if ($modul === null) {
            $this->stderr("Modul nexus-wissen ist nicht aktiviert.\n");
        }
        return $modul;
    }

    private function sammlung(string $kennung): ?Sammlung
    {
        $s = $this->modul()?->sammlung($kennung);
        if ($s === null) {
            $this->stderr("Sammlung \"$kennung\" ist nicht eingestellt. Vorhandene: php yii wissen/liste\n");
        }
        return $s;
    }
}
