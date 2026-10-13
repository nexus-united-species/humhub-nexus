<?php

namespace nexus\modules\orientierung;

/**
 * Leichter zurechtfinden (13.10.2026) -- auf Wunsch eines Mitglieds, das schlecht sieht.
 *
 * 1. Zurueck an dieselbe Stelle: Wer in "Letzte Aktivitaeten" (Kasten rechts auf der Uebersicht) einen
 *    Eintrag anklickt und mit "Zurueck" wiederkommt, landet wieder bei genau diesem Eintrag -- HumHub
 *    laedt den Kasten sonst neu von oben, mit nur den ersten 10 Eintraegen.
 * 2. Beitraege & Kommentare: neuer Punkt im Profilmenue jedes Menschen. Die Profilseite zeigt nur
 *    Beitraege, keine Kommentare -- dabei schreiben die meisten vor allem Kommentare. Die Liste zeigt
 *    beides, neueste zuerst, je 50, und nur, was der Lesende selbst sehen darf.
 */
class Module extends \humhub\components\Module
{
    /** @var int Eintraege je Seite in "Beitraege & Kommentare" */
    public $proSeite = 50;

    public function getName()
    {
        return 'N.E.X.U.S. Orientation';
    }

    public function getDescription()
    {
        return 'Back to the same place in "Latest activities", and a list of all posts and comments of a person.';
    }
}
