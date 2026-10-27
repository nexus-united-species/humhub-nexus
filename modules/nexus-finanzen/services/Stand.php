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
    /** @var array<int, array<string, array{betrag: int, geschaetzt: bool, von: int[]}>> kachel_id => monat ('' = einmalig) */
    private array $vorgestreckt = [];
    private int $vorgestrecktGesamt = 0;
    /** @var array<int, array<string, int>> kachel_id => Monat => alles Bezahlte (Topf + vorgestreckt) */
    private array $bezahltMonat = [];
    /** @var array<int, array<string, int>> kachel_id => Monat => davon vorgestreckt */
    private array $vorgestrecktMonat = [];
    /** @var array<int, array<string, bool>> kachel_id => Monat => mindestens ein Betrag ist geschaetzt */
    private array $bezahltGeschaetzt = [];
    /** @var array<int, string> user_id => Anzeigename */
    private array $namen = [];

    /** @param string|null $monat JJJJ-MM: Kacheln, die erst spaeter beginnen (ab_monat), bleiben dann weg */
    public function __construct(?string $monat = null)
    {
        foreach (Kachel::find()->orderBy(['rang' => SORT_ASC, 'id' => SORT_ASC])->all() as $k) {
            if ($monat !== null && $k->ab_monat !== null && $k->ab_monat > $monat) {
                continue;
            }
            $this->kacheln[(int)$k->id] = $k;
        }
        $db = \Yii::$app->db;
        foreach ($db->createCommand('SELECT kachel_id, COALESCE(monat, \'\') m, SUM(betrag) s FROM nexus_fin_zuweisung GROUP BY kachel_id, m')->queryAll() as $r) {
            $this->zugewiesen[(int)$r['kachel_id']][$r['m']] = (int)$r['s'];
        }
        // Nur Ausgaben aus dem Spendentopf; Vorgestrecktes steht getrennt (verkleinert den Topf nicht)
        foreach ($db->createCommand('SELECT kachel_id, SUM(betrag) s FROM nexus_fin_ausgabe WHERE status = \'freigegeben\' AND vorgestreckt_von IS NULL GROUP BY kachel_id')->queryAll() as $r) {
            $this->ausgegeben[(int)$r['kachel_id']] = (int)$r['s'];
        }
        foreach ($db->createCommand('SELECT kachel_id, COALESCE(monat, \'\') m, vorgestreckt_von v, SUM(betrag) s, MAX(geschaetzt) g FROM nexus_fin_ausgabe WHERE status = \'freigegeben\' AND vorgestreckt_von IS NOT NULL GROUP BY kachel_id, m, v')->queryAll() as $r) {
            $eintrag = $this->vorgestreckt[(int)$r['kachel_id']][$r['m']] ?? ['betrag' => 0, 'geschaetzt' => false, 'von' => []];
            $eintrag['betrag'] += (int)$r['s'];
            $eintrag['geschaetzt'] = $eintrag['geschaetzt'] || (int)$r['g'] === 1;
            $eintrag['von'][] = (int)$r['v'];
            $this->vorgestreckt[(int)$r['kachel_id']][$r['m']] = $eintrag;
            $this->vorgestrecktGesamt += (int)$r['s'];
            $this->namen[(int)$r['v']] ??= (\humhub\modules\user\models\User::findOne((int)$r['v'])?->displayName ?? '?');
        }
        // Alles Bezahlte je Kachel und Monat (nach Datum der Zahlung), egal ob aus dem Topf oder vorgestreckt
        foreach ($db->createCommand('SELECT kachel_id, LEFT(datum, 7) m, (vorgestreckt_von IS NOT NULL) v, SUM(betrag) s, MAX(geschaetzt) g FROM nexus_fin_ausgabe WHERE status = \'freigegeben\' GROUP BY kachel_id, m, v')->queryAll() as $r) {
            $this->bezahltMonat[(int)$r['kachel_id']][$r['m']] = ($this->bezahltMonat[(int)$r['kachel_id']][$r['m']] ?? 0) + (int)$r['s'];
            $this->bezahltGeschaetzt[(int)$r['kachel_id']][$r['m']] = ($this->bezahltGeschaetzt[(int)$r['kachel_id']][$r['m']] ?? false) || (int)$r['g'] === 1;
            if ((int)$r['v'] === 1) {
                $this->vorgestrecktMonat[(int)$r['kachel_id']][$r['m']] = (int)$r['s'];
            }
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

    /**
     * Alles, was fuer die Kachel bezahlt wurde (aus den Spenden + vorgestreckt): im Monat (laufend) bzw. insgesamt (einmalig).
     * @return array{betrag: int, geschaetzt: bool}|null
     */
    public function bezahlt(Kachel $k, string $monat): ?array
    {
        $je = $this->bezahltMonat[(int)$k->id] ?? [];
        $betrag = $k->istLaufend() ? ($je[$monat] ?? 0) : array_sum($je);
        if ($betrag <= 0) {
            return null;
        }
        $g = $this->bezahltGeschaetzt[(int)$k->id] ?? [];
        return ['betrag' => $betrag, 'geschaetzt' => $k->istLaufend() ? ($g[$monat] ?? false) : in_array(true, $g, true)];
    }

    /**
     * Aus eigener Tasche vorgestreckt: im Monat (laufend) bzw. insgesamt (einmalig). Leer = nichts.
     * @return array{betrag: int, geschaetzt: bool, von: string[]}|null
     */
    public function vorgestreckt(Kachel $k, string $monat): ?array
    {
        $je = $this->vorgestreckt[(int)$k->id] ?? [];
        $treffer = $k->istLaufend() ? array_filter([$je[$monat] ?? null]) : $je;
        if ($treffer === []) {
            return null;
        }
        $betrag = 0;
        $geschaetzt = false;
        $von = [];
        foreach ($treffer as $t) {
            $betrag += $t['betrag'];
            $geschaetzt = $geschaetzt || $t['geschaetzt'];
            foreach ($t['von'] as $id) {
                $von[$id] = $this->namen[$id] ?? '?';
            }
        }
        return ['betrag' => $betrag, 'geschaetzt' => $geschaetzt, 'von' => array_values($von)];
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
     * @return array{eingegangen: int, im_monat: int, reserviert: int, ausgegeben: int, frei: int, vorgestreckt: int}
     */
    public function summen(string $monat): array
    {
        $zugewiesen = 0;
        foreach ($this->zugewiesen as $je) {
            $zugewiesen += array_sum($je);
        }
        $ausgegeben = array_sum($this->ausgegeben);
        $bezahltMonat = $vorgestrecktMonat = $bezahltGesamt = 0;
        foreach ($this->bezahltMonat as $je) {
            $bezahltMonat += $je[$monat] ?? 0;
            $bezahltGesamt += array_sum($je);
        }
        foreach ($this->vorgestrecktMonat as $je) {
            $vorgestrecktMonat += $je[$monat] ?? 0;
        }
        $spendenMonat = $bezahltMonat - $vorgestrecktMonat;
        return [
            'bezahlt_monat' => $bezahltMonat,
            'vorgestreckt_monat' => $vorgestrecktMonat,
            'spenden_monat' => $spendenMonat,
            // Aus den Spenden bezahlt, aber nicht in diesem Monat eingegangen: Rest aus frueheren Monaten
            'rest_vormonate' => max(0, $spendenMonat - ($this->eingangMonat[$monat] ?? 0)),
            'bezahlt_gesamt' => $bezahltGesamt,
            'eingegangen' => $this->eingangGesamt,
            'im_monat' => $this->eingangMonat[$monat] ?? 0,
            'reserviert' => max(0, $zugewiesen - $ausgegeben),
            'ausgegeben' => $ausgegeben,
            'frei' => $this->topf(),
            'vorgestreckt' => $this->vorgestrecktGesamt,
        ];
    }

    /**
     * Ziel und Deckung eines Bereichs. "ziel" = alle Kacheln (auch die noch grauen), damit der geplante
     * Bedarf sichtbar ist; "freigegeben" = nur die beschlossenen; gedeckt zaehlt je Kachel hoechstens ihren Bedarf.
     * "bezahlt" = was in diesem Monat tatsaechlich bezahlt wurde (Topf + vorgestreckt), unabhaengig von Ampel und Freigabe.
     * @return array{ziel: int, freigegeben: int, gedeckt: int, prozent: int, bezahlt: int}
     */
    public function bereich(string $bereich, string $monat, bool $nurOeffentlich = false): array
    {
        $ziel = $frei = $gedeckt = $bezahlt = 0;
        foreach ($this->kacheln($bereich) as $k) {
            if ($nurOeffentlich && !$k->oeffentlich) {
                continue;
            }
            $bezahlt += $this->bezahltMonat[(int)$k->id][$monat] ?? 0;
            $ziel += (int)$k->betrag;
            if ($k->freigegeben) {
                $frei += (int)$k->betrag;
                $gedeckt += min((int)$k->betrag, $this->gedeckt($k, $monat));
            }
        }
        return ['ziel' => $ziel, 'freigegeben' => $frei, 'gedeckt' => $gedeckt,
            'prozent' => $frei > 0 ? (int)floor(100 * $gedeckt / $frei) : 0, 'bezahlt' => $bezahlt];
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
            if (!$k->freigegeben || $k->zusatz === 'pausiert' || ($k->ab_monat !== null && $k->ab_monat > $monat)) {
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
