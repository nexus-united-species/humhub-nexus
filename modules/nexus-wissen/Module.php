<?php

namespace nexus\modules\wissen;

use humhub\modules\space\models\Space;
use nexus\modules\wissen\services\Sammlung;

/**
 * Wissenssammlungen: Nachschlagewerke "wie Wikipedia", jeweils in einem Kreis (Space).
 * Eine Sammlung ist z. B. ein Gesundheitswissen in einem Kreis -- weitere Kreise koennen eigene haben.
 *
 * Rollen je Sammlung:
 * - Lesen: alle angemeldeten Menschen, die den Kreis oeffnen koennen -- erst nach der Freigabe
 *   (Knopf auf der Startseite der Sammlung). Vorher sehen sie nur die Bearbeitenden.
 * - Vorschlagen ("Ich moechte etwas ergaenzen / aendern") und "Im Kreis darueber sprechen":
 *   Mitglieder des Kreises.
 * - Bearbeiten: Systemadmins (mit 'kreisAdmins' => true auch die Admins des Kreises). Jede Aenderung
 *   wird als Version gespeichert.
 *
 * Bewusst KEIN offenes Wiki: Die Texte sind redaktionell geprueft; jede freie Aenderung wuerde diese
 * Pruefung aushebeln. Dafuer gibt es die Vorschlaege.
 */
class Module extends \humhub\components\Module
{
    /*
     * Einstellungen / settings: protected/config/common.php
     *   'modules' => ['nexus-wissen' => ['sammlungen' => [
     *       'handbuch' => ['kreis' => 5, 'name' => 'Handbuch', 'importPfad' => '/data/wissen/handbuch'],
     *   ]]]
     * Alle Schluessel einer Sammlung: services/Sammlung.php und README.md.
     */

    /** @var array<string, array> Sammlungen: Kennung => Einstellungen */
    public $sammlungen = [];

    public static function instanz(): ?self
    {
        $modul = \Yii::$app->getModule('nexus-wissen');
        return $modul instanceof self ? $modul : null;
    }

    public function getName()
    {
        return 'N.E.X.U.S. Knowledge Base';
    }

    public function getDescription()
    {
        return 'Wikipedia-style reference collections inside spaces.';
    }

    public function sammlung(string $kennung): ?Sammlung
    {
        $einstellungen = $this->sammlungen[$kennung] ?? null;
        return is_array($einstellungen) && Sammlung::gueltigeKennung($kennung) ? new Sammlung($kennung, $einstellungen, $this) : null;
    }

    /** @return Sammlung[] */
    public function alleSammlungen(): array
    {
        $liste = [];
        foreach (array_keys((array)$this->sammlungen) as $kennung) {
            $s = $this->sammlung((string)$kennung);
            if ($s !== null) {
                $liste[] = $s;
            }
        }
        return $liste;
    }

    /** @return Sammlung[] die Sammlungen, die in diesem Kreis haengen */
    public function sammlungenIn(Space $kreis): array
    {
        return array_values(array_filter($this->alleSammlungen(), fn(Sammlung $s) => $s->kreisId() === (int)$kreis->id));
    }
}
