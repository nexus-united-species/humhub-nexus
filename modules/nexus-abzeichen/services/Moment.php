<?php

namespace nexus\modules\abzeichen\services;

/**
 * Ein Moment, der gefeiert wird. Einstellungen (Texte als String oder ['de' => ..., 'en' => ...]):
 *   art              Was zaehlt. Bisher: "aktivzeit" (aktive Minuten im Portal je Kalenderwoche)
 *   minutenProWoche  Schwelle fuer "aktivzeit", Vorgabe 120
 *   name             Name im eigenen Profil, z. B. "Wochen mitgebaut"
 *   feier            Text der kurzen Feier, z. B. "Danke! Deine 2 Stunden für diese Woche sind geschafft."
 *   symbol           Font-Awesome-Name, Vorgabe "trophy"
 *
 * Eine neue "art" braucht Code in services/Aktivzeit.php bzw. einen eigenen Zaehler -- die Feier,
 * der stille Zaehler und die Anzeige im Profil kommen dann von selbst.
 */
class Moment
{
    public const ART_AKTIVZEIT = 'aktivzeit';
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

    public function feier(): string
    {
        return Texte::waehle($this->e['feier'] ?? null) ?: $this->name();
    }

    public function symbol(): string
    {
        $s = (string)($this->e['symbol'] ?? '');
        return preg_match('/^[a-z0-9-]+$/', $s) ? $s : 'trophy';
    }
}
