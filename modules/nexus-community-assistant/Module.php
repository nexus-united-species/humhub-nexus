<?php

namespace nexus\modules\communityAssistant;

use humhub\components\Module as BaseModule;
use yii\helpers\Url;

class Module extends BaseModule
{
    /*
     * Einstellungen / settings: protected/config/common.php
     *   'modules' => ['nexus-community-assistant' => ['anleitungenKreis' => 2, ...]]
     * Bot-Konto, Kreise fuer Erinnerungen usw. kommen aus Umgebungsvariablen (siehe README).
     */

    /** @var int|null Kreis (Space-ID), dessen Wiki-Seiten die Anleitungen fuer Mitglieder sind. */
    public $anleitungenKreis = null;

    /** @var int|null Kreis, in dem sich neue Mitglieder vorstellen; leer = kein solcher Schritt. */
    public $vorstellungenKreis = null;

    /** @var int[] Kreise, die die Begruessung nie vorschlaegt (z. B. Standardkreise, interne Kreise). */
    public $nichtVorschlagen = [];

    /** @var int|null Kalendereintrag der regelmaessigen Willkommensrunde; leer = kein Hinweis. */
    public $willkommensrundeTermin = null;

    /** @var string|null Spendenseite fuer den Spendenaufruf; leer = kein Spendenaufruf. */
    public $spendenLink = null;

    public static function instanz(): self
    {
        return \Yii::$app->getModule('nexus-community-assistant');
    }

    public function getName()
    {
        return 'N.E.X.U.S. Nova (KI-Assistent)';
    }

    public function getDescription()
    {
        return 'Nova: Begruessung neuer Mitglieder, woechentliche Zusammenfassung, Meeting-Erinnerungen, Spendenaufruf/Selbstvorstellung und Direktfrage-Antworten ueber ein eigenes Bot-Konto.';
    }

    public function getConfigUrl()
    {
        return Url::to(['/nexus-community-assistant/admin/index']);
    }
}
