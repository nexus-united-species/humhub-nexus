<?php

namespace nexus\modules\karte;

/**
 * Karte der Gemeinschaften (Josh, 30.09.2026). Gemeinschaften gruenden sich zuerst im Portal
 * (HumHub), nicht in der OneApp -- die ist noch Alpha. Die Karte zeigt, wo es schon eine gibt
 * ("in meiner Naehe ist ja schon jemand") und ruft dazu auf, eine eigene zu gruenden.
 *
 * Drei Regeln, die im Code wie Willkuer aussehen koennten:
 * - Auf die Karte kommt NUR ein Kreis mit einer Zeile in nexus_gemeinschaft_ort. Diese Zeile legt
 *   ausschliesslich ein Systemadmin an ("ist eine Gemeinschaft"). Arbeitskreise und Themenkreise
 *   haben keine und koennen sich nicht selbst eintragen; Besitzer duerfen nur den ORT aendern.
 * - Nur Ort/Region, nie eine Adresse: die Ortssuche nimmt nur Orte/Regionen an, Koordinaten werden
 *   auf zwei Nachkommastellen (~1 km) gerundet, die Karte laesst sich nicht naeher als Stufe 11
 *   heranholen. Bei kleinen Gemeinschaften liesse sich sonst auf einzelne Menschen schliessen.
 * - Kartenbilder kommen ueber unseren Server (KarteController::actionKachel), nicht direkt von
 *   OpenStreetMap -- sonst saehe ein fremder Dienst, wer sich die Karte ansieht.
 */
class Module extends \humhub\components\Module
{
    /*
     * Einstellungen / settings: protected/config/common.php
     *   'modules' => ['nexus-karte' => ['webseiten' => ['https://example.org'], ...]]
     */

    /**
     * @var string[] Eigene Webseiten (Origin, z. B. "https://example.org"), die Kartenbilder und die
     * Datenquelle /nexus-karte/karte/daten nutzen duerfen. Leer = nur das Portal selbst.
     */
    public $webseiten = [];

    /** @var string|null Ziel des Knopfes "Gemeinschaft gruenden" fuer Gaeste; leer = Anmeldeseite. */
    public $registrierenUrl = null;

    /** @var int|null ID der Wiki-Seite "Deine Gemeinschaft gruenden"; leer = kein Knopf. */
    public $wikiGruenden = null;

    /** @var string|null Absenderangabe (User-Agent) fuer OpenStreetMap; leer = mit Portal-Adresse. */
    public $absender = null;

    public static function instanz(): self
    {
        return \Yii::$app->getModule('nexus-karte');
    }

    public function registrierenUrl(): string
    {
        return $this->registrierenUrl ?: \yii\helpers\Url::to(['/user/auth/login'], true);
    }

    public function absender(): string
    {
        return $this->absender
            ?: 'HumHub-Gemeinschaftskarte/1.0 (+' . \Yii::$app->settings->get('baseUrl') . ')';
    }

    /** @return string[] Hostnamen der erlaubten Webseiten (fuer den Referer-Vergleich). */
    public function webseitenHosts(): array
    {
        return array_values(array_filter(array_map(
            static fn($origin) => parse_url((string)$origin, PHP_URL_HOST),
            $this->webseiten
        )));
    }

    public function getName()
    {
        return 'N.E.X.U.S. Gemeinschaftskarte';
    }

    public function getDescription()
    {
        return 'Landkarte der Gemeinschaften mit Ort und Link zum Kreis.';
    }
}
