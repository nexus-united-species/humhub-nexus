<?php

namespace nexus\modules\teilen;

/**
 * Teilen und oeffentliche Lese-Links (02.10.2026).
 *
 * 1. "Teilen" unter jedem Beitrag und jeder Wiki-Seite: am Handy das Teilen-Menue des Handys
 *    (darin stehen Signal, WhatsApp, Telegram, E-Mail ... -- je nachdem, was installiert ist),
 *    am PC "Link kopieren". Der mitgeschickte Text steht in der Sprache dessen, der teilt --
 *    also uebersetzt, wenn er den Beitrag uebersetzt liest (Rueckmeldung aus dem Team: "nur das Original").
 *
 * 2. "Oeffentlich lesbar machen": ein einzelner Beitrag oder eine einzelne Wiki-Seite bekommt
 *    einen eigenen Link, unter dem man ihn OHNE Anmeldung lesen kann. Grundregel bleibt (Josh,
 *    13.09.2026): Gaeste sehen nichts -- ausser dem, was ein Mensch ausdruecklich freigegeben hat.
 *    Freigeben duerfen der Verfasser und Admins (Josh: "beide"). Gezeigt wird nur der Inhalt,
 *    ohne Namen ("aus der N.E.X.U.S.-Gemeinschaft"), ohne Kommentare, ohne Kreis.
 *    Zuruecknehmen jederzeit; der Link fuehrt dann ins Leere.
 */
class Module extends \humhub\components\Module
{
    /** Sprachen der Leseseite -- dieselben wie bei der automatischen Uebersetzung. */
    public const SPRACHEN = ['de', 'en', 'es'];

    /*
     * Einstellungen / settings: protected/config/common.php
     *   'modules' => ['nexus-teilen' => ['webseiten' => ['https://example.org'], ...]]
     */

    /** @var string[] Eigene Webseiten (Origin), die den Schaufenster-Feed im Browser abrufen duerfen. */
    public $webseiten = [];

    /** @var string|null Ziel von "Mitmachen – registrieren" auf den Leseseiten; leer = Anmeldeseite. */
    public $registrierenUrl = null;

    /** @var string|null Ziel von "Schon Mitglied? Anmelden"; leer = Anmeldeseite. */
    public $anmeldenUrl = null;

    /**
     * @var array<string, array<string, string>> Fusszeile der Leseseiten je Sprache, Beschriftung => Adresse,
     * z. B. ['de' => ['Impressum' => 'https://example.org/impressum'], 'en' => [...]]. Fehlt eine
     * Sprache, gilt Deutsch. Leer = keine Links.
     */
    public $fussLinks = [];

    /** @var string|null Kontakt-E-Mail in der Fusszeile; leer = keine. */
    public $kontaktEmail = null;

    public static function instanz(): self
    {
        return \Yii::$app->getModule('nexus-teilen');
    }

    public function registrierenUrl(): string
    {
        return $this->registrierenUrl ?: \yii\helpers\Url::to(['/user/auth/login'], true);
    }

    public function anmeldenUrl(): string
    {
        return $this->anmeldenUrl ?: \yii\helpers\Url::to(['/user/auth/login'], true);
    }

    /** @return array<string, string> Beschriftung => Adresse */
    public function fussLinks(string $sprache): array
    {
        return $this->fussLinks[$sprache] ?? $this->fussLinks['de'] ?? [];
    }

    public function getName()
    {
        return 'N.E.X.U.S. Teilen';
    }

    public function getDescription()
    {
        return 'Teilen-Knopf (Handy-Menue / Link kopieren, uebersetzt) und oeffentliche Lese-Links fuer einzeln freigegebene Beitraege und Wiki-Seiten.';
    }
}
