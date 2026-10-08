<?php

namespace nexus\modules\hilfe;

use humhub\modules\space\models\Space;

/**
 * Hilfe & Support (Josh, 07.10.2026: "die Menschen kontaktieren mich, das geht auf Dauer nicht").
 *
 * 1. Hilfe-Seite /nexus-hilfe/hilfe: Anleitungen (Kreis Willkommen), "Frag Nova", Anfrage-Formular --
 *    auch ohne Anmeldung (fuer alle, die nicht ins Portal kommen).
 * 2. Jede Anfrage wird ein Beitrag im PRIVATEN Kreis "Support-Team", mit Nummer.
 *    Das Team bekommt eine Portal-Nachricht (mit E-Mail), der Mensch eine Eingangsbestaetigung mit
 *    der Zusage "Antwort innerhalb von 24 Stunden".
 * 3. Was Nova nicht selbst klaeren kann, legt Nova ebenfalls hier als Anfrage an (statt an die Admins).
 *
 * Der Kreis wird ueber die Modul-Einstellung "kreis" gefunden (Space-ID).
 */
class Module extends \humhub\components\Module
{
    public const ANTWORT_STUNDEN = 24;

    /**
     * @var int|null Kreis (Space-ID), dessen Wiki-Seiten auf der Hilfe-Seite als Anleitungen stehen;
     * leer = keine Anleitungsliste. Einstellung in protected/config/common.php:
     * 'modules' => ['nexus-hilfe' => ['anleitungenKreis' => 2]].
     */
    public $anleitungenKreis = null;

    public function getName()
    {
        return 'N.E.X.U.S. Hilfe & Support';
    }

    public function getDescription()
    {
        return 'Hilfe-Seite, Frag Nova und Anfrage-Formular; Anfragen gehen an den Kreis Support-Team.';
    }

    public function supportKreis(): ?Space
    {
        $id = (int)$this->settings->get('kreis');
        return $id > 0 ? Space::findOne(['id' => $id]) : null;
    }
}
