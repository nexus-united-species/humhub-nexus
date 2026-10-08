<?php

namespace nexus\modules\mitgliedsanfrage;

/**
 * Mitgliedsanfrage wie frueher bei Telegram (Josh, 30.09.2026): Wer sich registriert, beantwortet
 * vier kurze Fragen und bestaetigt die Charta. Das ist KEINE Pruefung vor der Freischaltung --
 * der Mensch ist sofort drin; die Admins bekommen die Antworten und koennen danach entscheiden.
 *
 * Der Weg der Antworten:
 * 1. Authentik, Registrierungsseite (Stage nexus-registrierung-eingabe): vier Textfelder + Haken,
 *    gespeichert als Konto-Attribute nexus_aufmerksam / nexus_erwartung / nexus_faehigkeiten /
 *    nexus_einbringen / nexus_charta.
 * 2. Authentik gibt sie dem Portal bei der Anmeldung mit (eigene Weitergabe-Regel
 *    "NEXUS Mitgliedsanfrage" im Bereich "profile", Angabe "nexus_anfrage").
 * 3. Dieses Modul liest sie beim Login (Events::onLogin) und schreibt den Admins -- genau einmal
 *    je Mensch (Tabelle nexus_mitgliedsanfrage merkt sich, wer schon gemeldet ist).
 *
 * Bestandsmitglieder haben keine Antworten, fuer sie passiert nichts.
 */
class Module extends \humhub\components\Module
{
    /**
     * @var int|null Kreis (Space-ID), in den neue Mitglieder beim ersten Betreten geleitet werden;
     * leer = normales Ziel. Einstellung in protected/config/common.php:
     * 'modules' => ['nexus-mitgliedsanfrage' => ['willkommenKreis' => 2]].
     */
    public $willkommenKreis = null;

    public function getName()
    {
        return 'N.E.X.U.S. Mitgliedsanfrage';
    }

    public function getDescription()
    {
        return 'Meldet den Admins die Kennenlern-Antworten neuer Mitglieder.';
    }
}
