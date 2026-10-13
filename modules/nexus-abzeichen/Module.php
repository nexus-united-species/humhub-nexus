<?php

namespace nexus\modules\abzeichen;

use nexus\modules\abzeichen\services\Abzeichen;
use nexus\modules\abzeichen\services\Moment;
use Yii;

/**
 * Abzeichen und Momente (13.10.2026) -- Anerkennung ohne Wettbewerb.
 *
 * Zwei Arten, beliebig erweiterbar ueber common.php:
 * - Abzeichen (Bekenntnis): Man liest einen Text und bekennt sich mit einem Klick. Sichtbar als Rahmen
 *   und Siegel um das Profilbild, ueberall im Portal. Jederzeit loesbar. Gespeichert wird nur "ja/nein",
 *   kein Datum, keine Reihenfolge -- erstes Abzeichen: der Genesis-Pakt.
 * - Momente: kurze Feier, wenn man etwas erreicht hat (z. B. 2 Stunden aktiv im Portal in einer Woche),
 *   und ein stiller Zaehler, den NUR der Mensch selbst sieht. Keine Rangliste, kein Rot, kein Verlust.
 *
 * Die aktive Zeit misst echtes Tun (Seitenaufrufe und Absenden), nicht offene, unbenutzte Fenster:
 * Hintergrund-Abfragen zaehlen nicht, und Pausen ueber 10 Minuten zaehlen nur als 1 Minute.
 * Die Wochenzaehler werden nach einigen Wochen geloescht und sind fuer niemanden sonst sichtbar --
 * auch nicht fuer Admins. Das ist eine Freude, kein Kontrollinstrument.
 */
class Module extends \humhub\components\Module
{
    /*
     * Einstellungen / settings: protected/config/common.php
     *   'modules' => ['nexus-abzeichen' => [
     *       'abzeichen' => ['genesis-pakt' => ['name' => [...], 'kurz' => [...], 'textDatei' => '/data/abzeichen/genesis-pakt.md']],
     *       'momente' => ['portal-2-stunden' => ['art' => 'aktivzeit', 'minutenProWoche' => 120, 'name' => [...], 'feier' => [...]]],
     *       'ausnahmen' => [29],
     *   ]]
     * Alle Schluessel: services/Abzeichen.php, services/Moment.php, README.md.
     */

    /** @var array<string, array> Abzeichen zum Bekennen: Kennung => Einstellungen */
    public $abzeichen = [];

    /** @var array<string, array> Momente: Kennung => Einstellungen */
    public $momente = [];

    /** @var int[] Konten ohne Zeitzaehlung und Feiern (z. B. Bot-Konten) */
    public $ausnahmen = [];

    /** @var int Wochen, die Zeitzaehler aufbewahrt werden */
    public $wochenAufbewahren = 8;

    public static function instanz(): ?self
    {
        $modul = Yii::$app->getModule('nexus-abzeichen');
        return $modul instanceof self ? $modul : null;
    }

    public function getName()
    {
        return 'N.E.X.U.S. Badges';
    }

    public function getDescription()
    {
        return 'Badges members declare for themselves and private moments of thanks.';
    }

    public function abzeichen(string $kennung): ?Abzeichen
    {
        $e = $this->abzeichen[$kennung] ?? null;
        return is_array($e) && preg_match('/^[a-z0-9-]{1,64}$/', $kennung) ? new Abzeichen($kennung, $e) : null;
    }

    /** @return Abzeichen[] in der eingestellten Reihenfolge */
    public function alleAbzeichen(): array
    {
        return array_values(array_filter(array_map(fn($k) => $this->abzeichen((string)$k), array_keys((array)$this->abzeichen))));
    }

    /** @return Moment[] */
    public function alleMomente(): array
    {
        $liste = [];
        foreach ((array)$this->momente as $k => $e) {
            if (is_array($e) && preg_match('/^[a-z0-9-]{1,64}$/', (string)$k)) {
                $liste[] = new Moment((string)$k, $e);
            }
        }
        return $liste;
    }

    public function istAusnahme(int $userId): bool
    {
        return in_array($userId, array_map('intval', (array)$this->ausnahmen), true);
    }
}
