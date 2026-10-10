<?php

namespace nexus\modules\gesundheit;

use humhub\modules\space\models\Space;
use humhub\modules\space\models\Membership;
use humhub\modules\user\models\User;

/**
 * Gesundheitswissen (10.10.2026): ein Nachschlagewerk mit redaktionell geprueften Artikeln, eingehaengt
 * in einen Kreis (bei N.E.X.U.S. Kreis 9 "Gesundheit, Fuersorge & Pflege").
 *
 * Rollen:
 * - Lesen: alle angemeldeten Menschen -- aber erst nach der Freigabe (Moduleinstellung "freigegeben",
 *   Knopf auf der Startseite). Vorher sehen es nur Systemadmins.
 * - Vorschlagen ("Ich moechte etwas ergaenzen / aendern"): Mitglieder des Kreises. Der Vorschlag geht
 *   als Nachricht an die Admins.
 * - Aendern: nur Systemadmins. Jede Aenderung wird als Version gespeichert.
 *
 * Bewusst KEIN offenes Wiki: Die Texte sind geprueft (Quellen, keine Heilversprechen); jede freie
 * Aenderung wuerde diese Pruefung aushebeln.
 *
 * Die Artikel kommen aus einem Ordner mit manifest.json + Artikel/*.md (siehe commands/import.php).
 */
class Module extends \humhub\components\Module
{
    /*
     * Einstellungen / settings: protected/config/common.php
     *   'modules' => ['nexus-gesundheit' => ['kreis' => 14, 'importPfad' => '/data/gesundheit-import']]
     */

    /** @var int|null Space-ID des Kreises, in dem das Gesundheitswissen haengt. */
    public $kreis = null;

    /** @var string Ordner mit Redaktion/manifest.json und Artikel/ (fuer commands/import.php). */
    public $importPfad = '/data/gesundheit-import';

    public static function instanz(): self
    {
        return \Yii::$app->getModule('nexus-gesundheit');
    }

    public function getName()
    {
        return 'N.E.X.U.S. Gesundheitswissen';
    }

    public function getDescription()
    {
        return 'Nachschlagewerk mit geprueften Gesundheitsartikeln in einem Kreis.';
    }

    public function kreis(): ?Space
    {
        $id = (int)$this->kreis;
        return $id > 0 ? Space::findOne(['id' => $id]) : null;
    }

    public function freigegeben(): bool
    {
        return (bool)$this->settings->get('freigegeben', false);
    }

    public static function istAdmin(?User $mensch): bool
    {
        return $mensch !== null && $mensch->isSystemAdmin();
    }

    public function darfLesen(?User $mensch): bool
    {
        return $mensch !== null && ($this->freigegeben() || self::istAdmin($mensch));
    }

    public function darfVorschlagen(?User $mensch): bool
    {
        $kreis = $this->kreis();
        return $mensch !== null && $kreis !== null
            && ($kreis->isMember($mensch->id) || self::istAdmin($mensch));
    }
}
