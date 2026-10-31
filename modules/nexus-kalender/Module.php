<?php

namespace nexus\modules\kalender;

use Yii;

/**
 * Ein fertig gestalteter Kalender zum Umblaettern: eine einzige HTML-Datei mit allem darin.
 *
 * Die Datei liegt ausserhalb des oeffentlichen Bereichs und wird nur Angemeldeten ausgeliefert -- sie zeigt Namen und
 * Fotos von Mitgliedern. Eine neue Fassung einspielen heisst: index.html im Ordner ersetzen.
 *
 * Einstellungen in common.php (unter 'modules' => ['nexus-kalender' => [...]]). ERST den Code einspielen, dann
 * eintragen: Eine Eigenschaft, die der Code nicht kennt, legt das ganze Portal lahm.
 */
class Module extends \humhub\components\Module
{
    /** @var int[] Kreise (Space-IDs) mit Menuepunkt. Lesen duerfen alle Angemeldeten, auch ohne Menuepunkt. */
    public array $kreise = [];

    /** Jahr im Titel; leer = nur "Kalender" */
    public string $jahr = '';

    /** Ordner mit index.html und deckblatt.jpg */
    public string $ordner = '/data/nexus-kalender';

    /** Solange true: Hinweis "Entwurf" auf der Seite und kein Teilen in Kreise */
    public bool $entwurf = true;

    /** Adresse zum Bestellen; leer = kein Knopf */
    public string $bestellLink = '';

    public function getName()
    {
        return 'N.E.X.U.S. Kalender';
    }

    public function getDescription()
    {
        return 'Kalender zum Umblaettern fuer alle angemeldeten Mitglieder, mit Menuepunkt in ausgewaehlten Kreisen und Teilen in andere Kreise.';
    }

    public static function instanz(): ?self
    {
        $m = Yii::$app->getModule('nexus-kalender');
        return $m instanceof self ? $m : null;
    }

    public function datei(string $name): string
    {
        return rtrim($this->ordner, '/') . '/' . $name;
    }
}
