<?php

namespace nexus\modules\finanzen\services;

use nexus\modules\finanzen\models\Kachel;

/**
 * Der Finanzierungsstand -- alles wird aus den Buchungen berechnet, nichts von Hand gesetzt.
 * Ein Objekt je Anfrage (keine statischen Zwischenspeicher: im Worker-Modus von FrankenPHP leben die
 * ueber viele Anfragen weiter).
 *
 * Ampel einer Kachel:
 *  - grau: nicht freigegeben ("geplant / zu pruefen")
 *  - rot / gelb / gruen: 0 / teilweise / ganz durch zugewiesenes Geld gedeckt
 * Laufend = je Monat. Was ueber den Monatsbedarf hinaus zugewiesen war, wird in den naechsten Monat
 * DERSELBEN Kachel uebertragen (Vorausfinanzierung). Einmalig = Summe aller Zuweisungen, verfaellt nie.
 * Freies Geld, das nach der Verteilung uebrig ist, bleibt im Topf und steht im naechsten Vorschlag wieder bereit.
 */
class Stand
{
    /** @var Kachel[] id => Kachel */
    private array $kacheln = [];
    /** @var array<int, array<string, int>> kachel_id => monat ('' = einmalig) => Cent */
    private array $zugewiesen = [];
    /** @var array<int, int> kachel_id => freigegebene Ausgaben */
    private array $ausgegeben = [];
    private int $eingangFrei = 0;
    private int $eingangGesamt = 0;
    private int $ausTopf = 0;
    private int $fremdwaehrung = 0;
    private array $eingangMonat = [];

    public function __construct()
    {
        foreach (Kachel::find()->orderBy(['rang' => SORT_ASC, 'id' => SORT_ASC])->all() as $k) {
            $this->kacheln[(int)$k->id] = $k;
        }
        $db = \Yii::$app->db;
        foreach ($db->createCommand('SELECT kachel_id, COALESCE(monat, \'\') m, SUM(betrag) s FROM nexus_fin_zuweisung GROUP BY kachel_id, m')->queryAll() as $r) {
            $this->zugewiesen[(int)$r['kachel_id']][$r['m']] = (int)$r['s'];
        }
        foreach ($db->createCommand('SELECT kachel_id, SUM(betrag) s FROM nexus_fin_ausgabe WHERE status = \'freigegeben\' GROUP BY kachel_id')->queryAll() as $r) {
            $this->ausgegeben[(int)$r['kachel_id']] = (int)$r['s'];
        }
        foreach ($db->createCommand('SELECT waehrung, zweck_kachel_id IS NULL frei, LEFT(datum, 7) m, SUM(betrag) s, COUNT(*) n FROM nexus_fin_eingang GROUP BY waehrung, frei, m')->queryAll() as $r) {
            if ($r['waehrung'] !== 'EUR') {
                $this->fremdwaehrung += (int)$r['n'];
                continue;
            }
            $this->eingangGesamt += (int)$r['s'];
            $this->eingangMonat[$r['m']] = ($this->eingangMonat[$r['m']] ?? 0) + (int)$r['s'];
            if ((int)$r['frei'] === 1) {
                $this->eingangFrei += (int)$r['s'];
            }
        }
        $this->ausTopf = (int)$db->createCommand('SELECT COALESCE(SUM(betrag), 0) FROM nexus_fin_zuweisung WHERE eingang_id IS NULL')->queryScalar();
    }

    /** @return Kachel[] */
    public function kacheln(?string $bereich = null): array
    {
        return array_values(array_filter($this->kacheln, fn(Kachel $k) => $bereich === null || $k->bereich === $bereich));
    }

    public function kachel(int $id): ?Kachel
    {
        return $this->kacheln[$id] ?? null;
    }

    /** Gedeckt im Monat (laufend, inkl. Uebertrag) bzw. insgesamt (einmalig) */
    public function gedeckt(Kachel $k, string $monat): int
    {
        $z = $this->zugewiesen[(int)$k->id] ?? [];
        if (!$k->istLaufend()) {
            return array_sum($z);
        }
        return ($z[$monat] ?? 0) + $this->uebertrag($k, $monat);
    }

    /** Ueberschuss der Vormonate, der in diesen Monat derselben Kachel uebergeht */
    public function uebertrag(Kachel $k, string $monat): int
    {
        $z = $this->zugewiesen[(int)$k->id] ?? [];
        unset($z['']);
        $frueher = array_filter(array_keys($z), fn($m) => $m < $monat);
        if ($frueher === []) {
            return 0;
        }
        $carry = 0;
        for ($m = min($frueher); $m < $monat; $m = Geld::nachMonat($m)) {
            $carry = max(0, $carry + ($z[$m] ?? 0) - (int)$k->betrag);
        }
        return $carry;
    }

    public function fehlt(Kachel $k, string $monat): int
    {
        return max(0, (int)$k->betrag - $this->gedeckt($k, $monat));
    }

    /** grau|rot|gelb|gruen */
    public function farbe(Kachel $k, string $monat): string
    {
        if (!$k->freigegeben) {
            return 'grau';
        }
        $g = $this->gedeckt($k, $monat);
        if ($g <= 0) {
            return (int)$k->betrag === 0 ? 'gruen' : 'rot';
        }
        return $g >= (int)$k->betrag ? 'gruen' : 'gelb';
    }

    public function prozent(Kachel $k, string $monat): int
    {
        return (int)$k->betrag > 0 ? (int)min(100, floor(100 * $this->gedeckt($k, $monat) / (int)$k->betrag)) : 100;
    }

    public function ausgegeben(Kachel $k): int
    {
        return $this->ausgegeben[(int)$k->id] ?? 0;
    }

    /** Freies Geld im Topf (nur EUR) */
    public function topf(): int
    {
        return $this->eingangFrei - $this->ausTopf;
    }

    public function fremdwaehrungAnzahl(): int
    {
        return $this->fremdwaehrung;
    }

    /**
     * Uebersicht: eingegangen, reserviert (zugewiesen, noch nicht ausgegeben), ausgegeben, frei verfuegbar.
     * @return array{eingegangen: int, im_monat: int, reserviert: int, ausgegeben: int, frei: int}
     */
    public function summen(string $monat): array
    {
        $zugewiesen = 0;
        foreach ($this->zugewiesen as $je) {
            $zugewiesen += array_sum($je);
        }
        $ausgegeben = array_sum($this->ausgegeben);
        return [
            'eingegangen' => $this->eingangGesamt,
            'im_monat' => $this->eingangMonat[$monat] ?? 0,
            'reserviert' => max(0, $zugewiesen - $ausgegeben),
            'ausgegeben' => $ausgegeben,
            'frei' => $this->topf(),
        ];
    }

    /**
     * Ziel und Deckung eines Bereichs. "ziel" = alle Kacheln (auch die noch grauen), damit der geplante
     * Bedarf sichtbar ist; "freigegeben" = nur die beschlossenen; gedeckt zaehlt je Kachel hoechstens ihren Bedarf.
     * @return array{ziel: int, freigegeben: int, gedeckt: int, prozent: int}
     */
    public function bereich(string $bereich, string $monat, bool $nurOeffentlich = false): array
    {
        $ziel = $frei = $gedeckt = 0;
        foreach ($this->kacheln($bereich) as $k) {
            if ($nurOeffentlich && !$k->oeffentlich) {
                continue;
            }
            $ziel += (int)$k->betrag;
            if ($k->freigegeben) {
                $frei += (int)$k->betrag;
                $gedeckt += min((int)$k->betrag, $this->gedeckt($k, $monat));
            }
        }
        return ['ziel' => $ziel, 'freigegeben' => $frei, 'gedeckt' => $gedeckt,
            'prozent' => $frei > 0 ? (int)floor(100 * $gedeckt / $frei) : 0];
    }

    /**
     * Vorschlag fuer die Verteilung des freien Topfs im Monat: nach Rang, je Kachel hoechstens das, was fehlt.
     * Nur freigegebene, nicht pausierte Kacheln. Bucht NICHTS.
     * @return array<int, array{kachel_id: int, betrag: int}>
     */
    public function vorschlag(string $monat): array
    {
        $topf = $this->topf();
        $posten = [];
        foreach ($this->kacheln as $k) {
            if ($topf <= 0) {
                break;
            }
            if (!$k->freigegeben || $k->zusatz === 'pausiert') {
                continue;
            }
            $betrag = min($topf, $this->fehlt($k, $monat));
            if ($betrag > 0) {
                $posten[] = ['kachel_id' => (int)$k->id, 'betrag' => $betrag];
                $topf -= $betrag;
            }
        }
        return $posten;
    }

    /** Summe der freigegebenen Entwicklungskacheln (fuer den Deckel des Fonds) */
    public function entwicklungFreigegeben(?int $ohne = null): int
    {
        $s = 0;
        foreach ($this->kacheln('entwicklung') as $k) {
            if ($k->freigegeben && (int)$k->id !== (int)$ohne) {
                $s += (int)$k->betrag;
            }
        }
        return $s;
    }
}
