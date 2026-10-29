<?php

namespace nexus\modules\abzeichen\services;

/**
 * Ein Moment, der gefeiert wird. Einstellungen (Texte als String oder ['de' => ..., 'en' => ...]):
 *   art              Was zaehlt:
 *                    "aktivzeit"      Wochen, in denen jemand mindestens minutenProWoche im Portal aktiv war
 *                    "arbeitsstunden" freigegebene Stunden aus dem Modul nexus-arbeitszeit (Summe)
 *   minutenProWoche  Schwelle fuer "aktivzeit", Vorgabe 120
 *   name             Name im eigenen Profil, z. B. "Im Portal unterwegs"
 *   einheit          Einzahl|Mehrzahl, z. B. "Woche|Wochen" (oder nur "Wochen"); fuer Russisch drei Formen "неделя|недели|недель"
 *   feier            Text der kurzen Feier. Platzhalter {wert}, {einheit}, {stufe}.
 *                    "aktivzeit" feiert jede erreichte Woche, "arbeitsstunden" nur jede neue Stufe.
 *   stufen           [Schwelle => Name], z. B. [4 => 'Regelmäßig dabei', 12 => ...] (Name als String oder je Sprache)
 *   teilenText       Beitragstext beim Teilen, Platzhalter wie bei feier
 *   symbol           Font-Awesome-Name, Vorgabe "trophy"
 *
 * Der Wert ("anzahl") ist bei "aktivzeit" die Zahl der Wochen, bei "arbeitsstunden" die hoechste
 * erreichte Stufe in Stunden. Er sinkt nie -- kein Verlust.
 */
class Moment
{
    public const ART_AKTIVZEIT = 'aktivzeit';
    public const ART_ARBEITSSTUNDEN = 'arbeitsstunden';
    private const VORGABE_MINUTEN = 120;

    public function __construct(public readonly string $kennung, private readonly array $e)
    {
    }

    public function art(): string
    {
        return (string)($this->e['art'] ?? self::ART_AKTIVZEIT);
    }

    public function minutenProWoche(): int
    {
        return max(1, (int)($this->e['minutenProWoche'] ?? self::VORGABE_MINUTEN));
    }

    public function name(): string
    {
        return Texte::waehle($this->e['name'] ?? null) ?: $this->kennung;
    }

    public function einheit(int $wert = 2): string
    {
        $formen = explode('|', Texte::waehle($this->e['einheit'] ?? null), 3);
        // Drei Formen (Russisch: 1 Woche | 2-4 Wochen | 5 Wochen): 1, 21 ... / 2-4, 22-24 ... / alle anderen
        if (isset($formen[2])) {
            $rest10 = $wert % 10;
            $rest100 = $wert % 100;
            if ($rest10 === 1 && $rest100 !== 11) {
                return $formen[0];
            }
            return $rest10 >= 2 && $rest10 <= 4 && ($rest100 < 12 || $rest100 > 14) ? $formen[1] : $formen[2];
        }
        return $wert === 1 || !isset($formen[1]) ? $formen[0] : $formen[1];
    }

    public function symbol(): string
    {
        $s = (string)($this->e['symbol'] ?? '');
        return preg_match('/^[a-z0-9-]+$/', $s) ? $s : 'trophy';
    }

    /** @return array<int, string> Schwelle => Name, aufsteigend */
    public function stufen(): array
    {
        $liste = [];
        foreach ((array)($this->e['stufen'] ?? []) as $schwelle => $name) {
            if ((int)$schwelle > 0) {
                $liste[(int)$schwelle] = Texte::waehle($name);
            }
        }
        ksort($liste);
        return $liste;
    }

    /** Hoechste erreichte Stufe: [Schwelle, Name] oder null */
    public function stufeBei(int $wert): ?array
    {
        $treffer = null;
        foreach ($this->stufen() as $schwelle => $name) {
            if ($wert >= $schwelle) {
                $treffer = [$schwelle, $name];
            }
        }
        return $treffer;
    }

    /** Naechste Stufe: [Schwelle, Name] oder null */
    public function naechsteStufe(int $wert): ?array
    {
        foreach ($this->stufen() as $schwelle => $name) {
            if ($wert < $schwelle) {
                return [$schwelle, $name];
            }
        }
        return null;
    }

    /** Ist genau dieser Wert eine Stufe? (bei "aktivzeit": mit dieser Woche ist eine neue Stufe erreicht) */
    public function istStufe(int $wert): bool
    {
        return isset($this->stufen()[$wert]);
    }

    public function feier(int $wert): string
    {
        return $this->einsetzen(Texte::waehle($this->e['feier'] ?? null) ?: $this->name(), $wert);
    }

    public function teilenText(int $wert): string
    {
        $vorlage = Texte::waehle($this->e['teilenText'] ?? null);
        return $this->einsetzen($vorlage !== '' ? $vorlage : '🎉 {stufe} – ' . $this->name() . ': {wert} {einheit}', $wert);
    }

    /** "12 Wochen" */
    public function wertText(int $wert): string
    {
        return trim($wert . ' ' . $this->einheit($wert));
    }

    private function einsetzen(string $text, int $wert): string
    {
        $stufe = $this->stufeBei($wert);
        $text = strtr($text, ['{wert}' => (string)$wert, '{einheit}' => $this->einheit($wert), '{stufe}' => $stufe[1] ?? '']);
        // ohne Stufe bleiben sonst Reste wie "🎉  – " stehen
        return trim((string)preg_replace(['/\s+–\s+–/u', '/^(\S*)\s+–\s+/u', '/ {2,}/'], [' –', '$1 ', ' '], $text));
    }
}
