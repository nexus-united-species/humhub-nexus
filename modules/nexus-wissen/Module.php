<?php

namespace nexus\modules\wissen;

use humhub\modules\space\models\Space;
use nexus\modules\wissen\models\SammlungEintrag;
use nexus\modules\wissen\services\Sammlung;
use nexus\modules\wissen\services\Slug;
use Throwable;

/**
 * Wissenssammlungen: Nachschlagewerke "wie Wikipedia", jeweils in einem Kreis (Space).
 * Eine Sammlung ist z. B. ein Gesundheitswissen in einem Kreis -- weitere Kreise koennen eigene haben.
 *
 * Woher eine Sammlung kommt:
 * - aus den Einstellungen (common.php) -- mit Import-Ordner, KI-Beschreibung usw.
 * - oder im Portal angelegt von Besitzern und Admins des Kreises (Kreis-Menue oben rechts ->
 *   "Wissensbereich anlegen"), gespeichert in nexus_wissen_sammlung.
 *
 * Rollen je Sammlung:
 * - Lesen: alle angemeldeten Menschen, die den Kreis oeffnen koennen -- erst nach der Freigabe
 *   (Knopf auf der Startseite der Sammlung). Vorher sehen sie nur die Redaktion.
 * - Vorschlagen ("Ich moechte etwas ergaenzen / aendern") und "Im Kreis darueber sprechen":
 *   Mitglieder des Kreises.
 * - Redaktion: Systemadmins sowie Besitzer und Admins des Kreises. Sie schreiben Artikel (zuerst als
 *   Entwurf), laden Dateien hoch, pflegen die Kategorien und geben frei. Jede Aenderung wird als
 *   Version gespeichert.
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

    /**
     * @var string Ordner fuer Bilder in Artikeln (Pfad oder Yii-Alias). Leer: neben dem Einstellungsordner
     * ("nexus-wissen-bilder"). Darf NICHT im oeffentlichen Web-Verzeichnis liegen -- die Bilder werden
     * ueber die Sammlung ausgeliefert, damit nur Berechtigte sie sehen. Der Web-Server-Benutzer muss
     * dort schreiben duerfen.
     */
    public $bilderPfad = '';

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
        if (!Sammlung::gueltigeKennung($kennung)) {
            return null;
        }
        $einstellungen = $this->sammlungen[$kennung] ?? null;
        if (is_array($einstellungen)) {
            return new Sammlung($kennung, $einstellungen, $this);
        }
        $eintrag = $this->eigene(['kennung' => $kennung])[0] ?? null;
        return $eintrag !== null ? new Sammlung($kennung, $eintrag->einstellungen(), $this, true) : null;
    }

    /** @return Sammlung[] erst die aus den Einstellungen, dann die im Portal angelegten */
    public function alleSammlungen(): array
    {
        $liste = [];
        foreach (array_keys((array)$this->sammlungen) as $kennung) {
            $s = $this->sammlung((string)$kennung);
            if ($s !== null) {
                $liste[$s->kennung] = $s;
            }
        }
        foreach ($this->eigene() as $eintrag) {
            if (!isset($liste[$eintrag->kennung]) && Sammlung::gueltigeKennung($eintrag->kennung)) {
                $liste[$eintrag->kennung] = new Sammlung($eintrag->kennung, $eintrag->einstellungen(), $this, true);
            }
        }
        return array_values($liste);
    }

    /** @return Sammlung[] die Sammlungen, die in diesem Kreis haengen */
    public function sammlungenIn(Space $kreis): array
    {
        return array_values(array_filter($this->alleSammlungen(), fn(Sammlung $s) => $s->kreisId() === (int)$kreis->id));
    }

    /** Eine Kennung, die weder in den Einstellungen noch in der Datenbank vergeben ist. */
    public function freieKennung(string $wunsch): string
    {
        $basis = Slug::aus($wunsch, 50) ?: 'wissen';
        if (!Sammlung::gueltigeKennung($basis)) {
            $basis = 'wissen-' . $basis;
        }
        $kennung = $basis;
        for ($i = 2; isset($this->sammlungen[$kennung]) || SammlungEintrag::find()->where(['kennung' => $kennung])->exists(); $i++) {
            $kennung = $basis . '-' . $i;
        }
        return $kennung;
    }

    /** @return SammlungEintrag[] -- leer, solange die Tabelle noch fehlt (vor der Migration) */
    private function eigene(array $bedingung = []): array
    {
        try {
            return SammlungEintrag::find()->where($bedingung)->orderBy('id')->all();
        } catch (Throwable $e) {
            return [];
        }
    }
}
