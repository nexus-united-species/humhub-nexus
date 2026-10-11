<?php

namespace nexus\modules\wissen\commands;

use nexus\modules\wissen\models\Artikel;
use nexus\modules\wissen\Module;
use nexus\modules\wissen\services\Importer;
use nexus\modules\wissen\services\Sammlung;
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

    private const KI_DIENST = 'nexus\modules\communityAssistant\services\AiService';
    private const KI_TEXT_ZEICHEN = 6000;
    private const KI_VERSUCHE = 3;
    private const MAX_SCHLAGWORTE = 8;
    private const MAX_SUCHWOERTER = 15;

    private const ANWEISUNG = <<<'TXT'
Du verschlagwortest Artikel eines Nachschlagewerks, damit Menschen den passenden Artikel finden --
auch wenn sie andere Woerter benutzen als der Artikel. Das Nachschlagewerk: {beschreibung}

Antworte NUR mit JSON in genau dieser Form:
{"schlagworte": ["...", "..."], "suchwoerter": ["...", "..."]}

schlagworte: 4 bis 8 kurze, allgemein verstaendliche Begriffe (1-3 Woerter) in der Sprache des
Artikels: die wichtigsten Themen, Gegenstaende und Zielgruppen des Artikels. Verwende moeglichst
gaengige, wiederkehrende Begriffe statt sehr spezieller, damit viele Artikel dieselben teilen.

suchwoerter: 5 bis 15 Woerter, mit denen Laien nach diesem Thema suchen wuerden und die NICHT schon
im Titel stehen: Alltagswoerter, andere Bezeichnungen und Synonyme, Fachbegriffe und ihre
gebraeuchlichen Entsprechungen, typische Fragen oder Probleme, bei denen der Artikel hilft.

Erfinde nichts. Nur Begriffe, die zum Inhalt des Artikels passen.
TXT;

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
        if (!class_exists(self::KI_DIENST)) {
            $this->stderr("Fuer KI-Schlagworte wird das Modul nexus-community-assistant (mit nexus-translate) gebraucht.\n");
            return ExitCode::UNAVAILABLE;
        }
        $anzahlProbe = !$this->istProbe() ? 0 : ($this->probe === true ? 3 : max(1, (int)$this->probe));
        $dienst = self::KI_DIENST;
        $ki = new $dienst();
        $anweisung = $s->kiAnweisung() ?: str_replace('{beschreibung}', $s->kiBeschreibung() ?: $s->name('de'), self::ANWEISUNG);

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
            $daten = $this->frageKi($ki, $anweisung, "Titel: {$artikel->titel}\n\n" . mb_substr($artikel->inhalt, 0, self::KI_TEXT_ZEICHEN));
            if ($daten === null) {
                $fehler++;
                $this->stdout("FEHLER  {$artikel->titel}\n");
                continue;
            }
            $schlag = array_slice(array_map('strval', $daten['schlagworte']), 0, self::MAX_SCHLAGWORTE);
            $such = array_slice(array_map('strval', (array)($daten['suchwoerter'] ?? [])), 0, self::MAX_SUCHWOERTER);
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

    /** @return array{schlagworte: array, suchwoerter?: array}|null */
    private function frageKi($ki, string $anweisung, string $text): ?array
    {
        for ($versuch = 1; $versuch <= self::KI_VERSUCHE; $versuch++) {
            try {
                $antwort = $ki->frage($text, $anweisung, 0.2);
                if (preg_match('/\{.*\}/s', (string)$antwort, $m)) {
                    $daten = json_decode($m[0], true);
                    if (is_array($daten) && !empty($daten['schlagworte']) && is_array($daten['schlagworte'])) {
                        return $daten;
                    }
                }
            } catch (Throwable $e) {
                $this->stdout("  Versuch $versuch fehlgeschlagen: " . mb_substr($e->getMessage(), 0, 120) . "\n");
                sleep(3 * $versuch);
            }
        }
        return null;
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
